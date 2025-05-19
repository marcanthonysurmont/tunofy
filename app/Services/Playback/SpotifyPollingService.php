<?php

namespace App\Services\Playback;

use App\Models\Mix;
use App\Models\QueueSong;
use App\Models\PlaybackSession;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\CoDJUpdatedEvent;
use App\Services\Spotify\SpotifyService;
use App\Services\Queue\QueueManagementService;

class SpotifyPollingService
{
    protected $changeReason = '';

    public function __construct(
        protected SpotifyService $spotifyService,
        protected SongPlaybackService $songPlaybackService,
        protected QueueManagementService $queueManagementService,
        protected PlaybackStateManager $playbackState
    ) {
    }

    /**
     * Poll Spotify for the current playback state
     */
    public function pollPlayback(Mix $mix): ?array
    {
        // Clear stale flags based on timestamps rather than TTL
        $this->playbackState->clearStaleDeviceChangeFlags($mix);

        try {
            if ($this->playbackState->isQueueCompleted($mix)) {
                Log::info("Mix {$mix->id} queue completed, skipping polling");
                return null;
            }

            if ($this->playbackState->hasDeviceFailure($mix)) {
                Log::info("Mix {$mix->id} has device failure flag, stopping polling until user action");
                return [
                    'success' => false,
                    'action' => 'waiting_for_device_selection',
                    'message' => 'Waiting for user to select device and resume playback'
                ];
            }

            // Get the mix owner
            // Determine which user to use for playback
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

            // Check if we recently changed devices - if so, skip this poll cycle
            if ($this->playbackState->has($mix, PlaybackStateManager::DEVICE_CHANGED)) {
                Log::info("Mix {$mix->id} was just manually changed, skipping this poll");
                // Consume the flag after using it
                $this->playbackState->forget($mix, PlaybackStateManager::DEVICE_CHANGED);
                return null;
            }

            // Get current playback data from Spotify
            $playbackData = $this->spotifyService->getCurrentPlayback($user);

            // Store previous playback data for comparison
            $previousData = $this->playbackState->getPlaybackData($mix);

            // No active playback detected
            if (!$playbackData) {
                // Handle no playback case
                $this->handlePlayerState($mix, PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK);

                // Use the updateCacheAndBroadcast method to handle no playback data
                $this->updateCacheAndBroadcast($mix, null, $previousData);
                return null;
            }

            // Store the playback data in cache
            $this->playbackState->setPlaybackData($mix, $playbackData);

            // Get the currently playing song according to our queue
            $currentQueueSong = $this->songPlaybackService->getCurrentlyPlayingSong($mix);

            // Analyze the current playback state
            $playerState = $this->analyzePlayerState($mix, $currentQueueSong, $playbackData, $previousData);

            // Handle the player state - now with return value
            $result = $this->handlePlayerState($mix, $playerState, $playbackData, $currentQueueSong, $previousData);

            // Check for queue completion signal
            if ($result === PlaybackStateManager::QUEUE_COMPLETED) {
                // Set the queue_completed flag in cache
                $this->playbackState->setQueueCompleted($mix, true);
                return [
                    'success' => false,
                    'action' => 'stop_polling',
                    'message' => 'Queue completed'
                ];
            }

            return [
                'success' => true,
                'action' => 'continue',
                'message' => 'Polling completed successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error polling Spotify: " . $e->getMessage());
            return [
                'success' => false,
                'action' => 'error',
                'message' => "Polling error: " . $e->getMessage()
            ];
        }
    }

    /**
     * Analyze the current player state
     */
    private function analyzePlayerState(Mix $mix, ?QueueSong $currentQueueSong, array $playbackData, ?array $previousData = null): string
    {
        // NEW CHECK: Always check if mix is still active first
        if (!$mix->is_active) {
            Log::info("Mix {$mix->id} is inactive - skipping further analysis");
            return PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK;
        }

        // 1. Check for recent manual changes (user interactions take precedence)
        if ($this->shouldTrustManualChanges($mix)) {
            return PlaybackStateManager::PLAYER_STATE_NORMAL;
        }

        // 2. Handle case when no song is in our queue
        if (!$currentQueueSong) {
            return $this->analyzeStateWithoutCurrentSong($mix, $playbackData);
        }

        // 3. Check for no active playback
        if (empty($playbackData) || !isset($playbackData['item'])) {
            return PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK;
        }

        // 4. Check for track mismatch
        if ($this->hasTrackMismatch($mix, $currentQueueSong, $playbackData)) {
            return PlaybackStateManager::PLAYER_STATE_TRACK_MISMATCH;
        }

        // 5. Check if paused
        if (isset($playbackData['is_playing']) && $playbackData['is_playing'] === false) {
            return PlaybackStateManager::PAUSED;
        }

        // 6. Check for manual seek to end
        if ($this->isManualSeekToEnd($mix, $previousData, $currentQueueSong)) {
            return PlaybackStateManager::PLAYER_STATE_MANUAL_SEEK_END;
        }

        // 7. Check if song is ending
        $trackEndingStatus = $this->checkTrackEnding($mix, $previousData, $playbackData, $currentQueueSong);
        if ($trackEndingStatus !== null) {
            return $trackEndingStatus;
        }

        // 8. Check if playback is stuck
        if ($this->isPlaybackStuck($previousData, $playbackData)) {
            return PlaybackStateManager::PLAYER_STATE_STUCK;
        }

        // Default: normal playback
        return PlaybackStateManager::PLAYER_STATE_NORMAL;
    }

    // Helper methods for the analysis
    private function shouldTrustManualChanges(Mix $mix): bool
    {
        if (!$this->playbackState->has($mix, PlaybackStateManager::MANUAL_CHANGE)) {
            return false;
        }

        $manualChangeTime = $this->playbackState->get($mix, 'manual_change_timestamp');
        return $manualChangeTime && (time() - $manualChangeTime < 3);
    }

    private function analyzeStateWithoutCurrentSong(Mix $mix, array $playbackData): string
    {
        // Handle the case when no song is marked as playing in our system
        if (isset($playbackData['is_playing']) && $playbackData['is_playing'] === true) {
            return PlaybackStateManager::PLAYER_STATE_TRACK_MISMATCH;
        }
        return PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK;
    }

    private function hasTrackMismatch(Mix $mix, QueueSong $currentQueueSong, array $playbackData): bool
    {
        // First check if a track ID exists in the playback data
        if (!isset($playbackData['item']['id'])) {
            return false;
        }

        // Check if track IDs don't match
        if ($playbackData['item']['id'] !== $currentQueueSong->song->spotify_id) {
            // Don't report track mismatch during device change grace period
            if ($this->playbackState->hasDeviceChanged($mix)) {
                return false;
            }

            // ADDITIONAL CHECK: Don't report mismatch if we just started a manual track change
            if ($this->playbackState->has($mix, PlaybackStateManager::MANUAL_CHANGE)) {
                return false;
            }

            // ADDITIONAL CHECK: Don't report mismatch if the track in Spotify is the next intended track
            // This prevents the system from fighting against correct track progression
            $nextSong = $this->songPlaybackService->getNextSongToPlay($mix->id);
            if ($nextSong && $nextSong->song->spotify_id === $playbackData['item']['id']) {
                // This is actually the next song, not a mismatch
                return false;
            }

            return true;
        }
        return false;
    }

    private function checkTrackEnding(Mix $mix, ?array $previousData, array $playbackData, QueueSong $currentQueueSong): ?string
    {
        if (!isset($playbackData['item']['duration_ms']) || !isset($playbackData['progress_ms'])) {
            return null;
        }

        $durationMs = $playbackData['item']['duration_ms'];
        $progressMs = $playbackData['progress_ms'];
        $remainingMs = $durationMs - $progressMs;
        $percentRemaining = ($remainingMs / $durationMs) * 100;

        // Track ended when progress is very close to duration
        if ($remainingMs <= 2500 || $percentRemaining <= 1.0) {
            return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
        }

        // Check if track has changed from previous polling
        if ($this->hasTrackChanged($previousData, $playbackData)) {
            return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
        }

        // Check for stalled playback near end
        if ($this->isPlaybackStalledNearEnd($previousData, $playbackData, $percentRemaining)) {
            return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
        }

        // Update song progress in state manager (UI components)
        $this->updateProgressInformation($mix, $progressMs, $durationMs, $percentRemaining);

        return null;
    }

    /**
     * Check if the track has changed between polling cycles
     */
    private function hasTrackChanged(?array $previousData, array $playbackData): bool
    {
        if (!$previousData || !isset($previousData['item']['id']) || !isset($playbackData['item']['id'])) {
            return false;
        }

        return $previousData['item']['id'] !== $playbackData['item']['id'];
    }

    /**
     * Update progress information in state manager for UI components
     */
    private function updateProgressInformation(Mix $mix, int $progressMs, int $durationMs, float $percentRemaining): void
    {
        $this->playbackState->set($mix, PlaybackStateManager::SONG_PROGRESS, $progressMs);
        $this->playbackState->set($mix, PlaybackStateManager::SONG_DURATION, $durationMs);

        // If song is nearing end (less than 10% remaining), set the flag
        if ($percentRemaining <= 10) {
            $this->playbackState->set($mix, PlaybackStateManager::SONG_NEARING_END, true);
        } else {
            $this->playbackState->forget($mix, PlaybackStateManager::SONG_NEARING_END);
        }
    }

    /**
     * Check if playback is stalled near the end of the song
     */
    private function isPlaybackStalledNearEnd(?array $previousData, array $playbackData, float $percentRemaining): bool
    {
        if (!$previousData ||
            !isset($previousData['progress_ms']) ||
            !isset($playbackData['progress_ms'])) {
            return false;
        }

        // Only check if we're near the end of the song (less than 5% remaining)
        if ($percentRemaining > 5) {
            return false;
        }

        $progressDiff = abs($previousData['progress_ms'] - $playbackData['progress_ms']);

        // Progress barely changed when we're near the end - likely stalled
        return $progressDiff < 300;
    }

    /**
     * Check if user manually sought to near the end of the track
     */
    private function isManualSeekToEnd(Mix $mix, ?array $previousData, QueueSong $currentQueueSong): bool
    {
        $playbackData = $this->playbackState->getPlaybackData($mix);

        if (!$previousData ||
            !isset($previousData['progress_ms']) ||
            !isset($playbackData['progress_ms']) ||
            !isset($playbackData['item']['duration_ms'])) {
            return false;
        }

        $progressDiff = $playbackData['progress_ms'] - $previousData['progress_ms'];
        $durationMs = $playbackData['item']['duration_ms'];
        $currentPosition = $playbackData['progress_ms'];

        // Calculate how close we are to the end as a percentage
        $percentRemaining = 100 * (($durationMs - $currentPosition) / $durationMs);

        // Force track advancement when we're extremely close to the end (less than 3% remaining)
        if ($percentRemaining < 3) {
            Log::info("Forcing track advancement - extremely close to end ({$percentRemaining}% remaining)");
            return true;
        }

        // Large forward jump to near the end (jumped past 90% mark)
        if ($progressDiff > 5000 && ($currentPosition / $durationMs) > 0.9) {
            Log::info("Detected manual seek to near end");
            $this->playbackState->setSeekDetected($mix, $currentQueueSong->id, true);

            // If we're VERY close to the end (past 95%), immediately advance
            if (($currentPosition / $durationMs) > 0.95) {
                Log::info("Manual seek detected past 95% - triggering immediate advancement");
                return true;
            }
        }

        // Already detected seek and now very close to the end
        return $this->playbackState->has($mix, "seek:{$currentQueueSong->id}") &&
               ($currentPosition / $durationMs) > 0.97;
    }

    /**
     * Check if playback appears to be stuck
     */
    private function isPlaybackStuck(?array $previousData, ?array $playbackData): bool
    {
        if (
            !$previousData ||
            !isset($previousData['progress_ms']) ||
            !isset($playbackData['progress_ms']) ||
            !isset($previousData['item']['id']) ||
            $previousData['item']['id'] !== $playbackData['item']['id']
        ) {
            return false;
        }

        // Only consider stuck if Spotify says it's playing
        if (isset($playbackData['is_playing']) && !$playbackData['is_playing']) {
            return false;
        }

        $progressDiff = abs($previousData['progress_ms'] - $playbackData['progress_ms']);

        // Progress is stuck or barely changed
        return $progressDiff < 500;
    }

    /**
     * Handle the player state based on analysis
     */
    private function handlePlayerState(Mix $mix, string $playerState, ?array $playbackData = null, ?QueueSong $currentQueueSong = null, ?array $previousData = null)
    {
        // NEW CHECK: Don't handle player state for inactive mixes
        if (!$mix->is_active && $playerState !== PlaybackStateManager::QUEUE_COMPLETED) {
            Log::info("Not handling player state for inactive mix {$mix->id}");
            return null;
        }

        // Add protection at the beginning of the method
        if ($playerState === PlaybackStateManager::PLAYER_STATE_NORMAL && $playbackData === null) {
            Log::error("Received null playback data for NORMAL state in mix {$mix->id}");
            return; // Early return to prevent further processing
        }

        // Try harder to find device ID - check multiple patterns
        $deviceId = $this->playbackState->getDeviceId($mix);

        Log::info("Handling player state {$playerState} for mix {$mix->id}" .
                  ($deviceId ? " with device {$deviceId}" : " with no specific device"));

        switch ($playerState) {
            case PlaybackStateManager::QUEUE_COMPLETED:
                Log::info("Queue completion detected for mix {$mix->id}");

                // Mark all songs as finished
                QueueSong::where('mix_id', $mix->id)
                    ->whereIn('status', ['playing', 'pending'])
                    ->update([
                        'status' => 'finished',
                        'played_at' => now()
                    ]);

                // Mark session as inactive
                PlaybackSession::where('mix_id', $mix->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'ended_at' => now()
                    ]);

                $coDj = $mix->coDj;
                if ($coDj) {
                    $mix->update(['co_dj_id' => null]);
                    CoDJUpdatedEvent::dispatch($coDj);
                }

                CoDJUpdatedEvent::dispatch($mix->user);

                try {
                    $user = $mix->user; // Use the owner for pausing when queue completes
                    $this->spotifyService->pausePlayback($user);
                    Log::info("Paused playback after queue completion for mix {$mix->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to pause playback after queue completion: " . $e->getMessage());
                }
                
                // Set cache flag
                $this->playbackState->setQueueCompleted($mix, true);


                return PlaybackStateManager::QUEUE_COMPLETED;

            case PlaybackStateManager::PLAYER_STATE_TRACK_ENDED:
                Log::info("Detected track ended for mix {$mix->id}, advancing to next song");

                // Clear notification flags
                if (isset($currentQueueSong)) {
                    $this->playbackState->clearTrackEndNotification($mix, $currentQueueSong->song->spotify_id);
                }

                // Clear nearing end flag
                $this->playbackState->forget($mix, PlaybackStateManager::SONG_NEARING_END);

                // Explicitly log advancement attempt
                Log::info("Attempting to advance to next song for mix {$mix->id}");

                // Advance to next song
                $result = $this->songPlaybackService->advanceToNextSong($mix);

                // Log the result for debugging
                Log::info("Advance result: " . json_encode($result));
                break;

            case PlaybackStateManager::PLAYER_STATE_TRACK_MISMATCH:
                // Only try to fix mismatches if we're not in a device change grace period
                if (!$this->playbackState->hasDeviceChanged($mix)) {
                    Log::info("Detected track mismatch for mix {$mix->id}, resuming intended track");
                    $this->songPlaybackService->resumeIntendedTrack($mix);
                } else {
                    Log::info("Track mismatch detected but ignoring due to recent device change for mix {$mix->id}");
                }
                break;

            case PlaybackStateManager::PLAYER_STATE_STUCK:
                Log::info("Detected stuck playback for mix {$mix->id}, resuming playback");
                $this->songPlaybackService->resumeIntendedTrack($mix);
                break;

            case PlaybackStateManager::PLAYER_STATE_MANUAL_SEEK_END:
                Log::info("Detected manual seek to end for mix {$mix->id}, advancing to next song");
                $this->songPlaybackService->advanceToNextSong($mix);
                // Clear the seek detection flag
                if (isset($currentQueueSong)) {
                    $this->playbackState->forget($mix, "seek:{$currentQueueSong->id}");
                }
                break;

            case PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK:
                // If we know a song should be playing but nothing is playing
                if ($this->songPlaybackService->getCurrentlyPlayingSong($mix)) {
                    Log::info("No playback detected but song should be playing for mix {$mix->id}, resuming playback");
                    $this->songPlaybackService->resumeIntendedTrack($mix);
                }
                break;

            case PlaybackStateManager::PAUSED:
                Log::info("Detected paused playback for mix {$mix->id}, broadcasting pause event");

                if ($this->hasSignificantChanges($previousData, $playbackData)) {
                    event(new PlaybackDataUpdatedEvent($mix, $playbackData));
                } else {
                    Log::debug("Paused state unchanged for mix {$mix->id}, skipping broadcast");
                }
                break;

            case PlaybackStateManager::PLAYER_STATE_NORMAL:
                Log::info("Detected normal (playing) playback for mix {$mix->id}, broadcasting play event");

                // Fetch the CACHED playback data, not null playbackData parameter
                $cachedPlaybackData = $this->playbackState->getPlaybackData($mix);

                // Debug playback data
                Log::debug("Playback data: " . ($cachedPlaybackData ? 'Valid cached data' : 'null'));

                // Check if we have valid data
                if (!$cachedPlaybackData) {
                    Log::error("Invalid playback data for mix {$mix->id}: NULL");
                    return; // Early return
                }

                // Only proceed if we have significant changes
                if ($this->hasSignificantChanges($previousData, $cachedPlaybackData)) {
                    Log::info("Broadcasting playback change for mix {$mix->id}");
                    event(new PlaybackDataUpdatedEvent($mix, $cachedPlaybackData));
                } else {
                    Log::debug("No significant changes for mix {$mix->id} - skipping broadcast");
                }
                break;
        }

        // Add a default return
        return null;
    }

    /**
     * Update cache and broadcast player data
     */
    private function updateCacheAndBroadcast(Mix $mix, ?array $playbackData, ?array $previousData): void
    {
        // Handle no playback data case
        if (empty($playbackData) || !isset($playbackData['item'])) {
            $noPlaybackData = [
                'status' => 'no_active_playback',
                '_timestamp' => now()->timestamp
            ];

            $this->playbackState->setPlaybackData($mix, $noPlaybackData);

            // Only broadcast if previous state was different
            if (empty($previousData) ||
                isset($previousData['item']) ||
                ($previousData['status'] ?? '') !== 'no_active_playback') {

                Log::info("Broadcasting no active playback for mix {$mix->id}");
                event(new PlaybackDataUpdatedEvent($mix, $noPlaybackData));
            }
            return;
        }

        // Add timestamp to playback data
        $playbackData['_timestamp'] = now()->timestamp;

        // Always update cache
        $this->playbackState->setPlaybackData($mix, $playbackData);

        // Only broadcast if we have significant changes - even for active mixes
        if ($this->hasSignificantChanges($previousData, $playbackData)) {
            Log::info("Broadcasting playback change for mix {$mix->id}: {$this->changeReason}");
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));
        } else {
            Log::debug("No significant changes for mix {$mix->id} - skipping broadcast");
        }
    }

    /**
     * Determine if there are significant changes between previous and current playback data
     */
    private function hasSignificantChanges(?array $previousData, ?array $playbackData): bool
    {
        if (!$previousData || !$playbackData) {
            return true;
        }

        // Detect change in play/pause state
        if (($previousData['is_playing'] ?? null) !== ($playbackData['is_playing'] ?? null)) {
            return true;
        }

        // Detect change in track
        if (($previousData['item']['id'] ?? null) !== ($playbackData['item']['id'] ?? null)) {
            return true;
        }

        // Only consider a seek significant if it's a big jump (e.g., >5s)
        if (abs(($previousData['progress_ms'] ?? 0) - ($playbackData['progress_ms'] ?? 0)) > 5000) {
            return true;
        }

        return false;
    }
}
