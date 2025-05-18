<?php

namespace App\Services;

use App\Models\Mix;
use App\Models\QueueSong;
use App\Models\PlaybackSession;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\CoDJUpdatedEvent;

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
        // Enhanced manual control check - give more time for operations to complete
        if ($this->playbackState->has($mix, PlaybackStateManager::MANUAL_CHANGE)) {
            // If a manual change was very recent (within 3 seconds), trust the UI state
            // over what Spotify reports - makes UI feel more responsive
            $manualChangeTime = $this->playbackState->get($mix, 'manual_change_timestamp');
            if ($manualChangeTime && (time() - $manualChangeTime < 3)) {
                Log::info("Detected recent manual control for mix {$mix->id}, delaying state analysis");
                return PlaybackStateManager::PLAYER_STATE_NORMAL; // Trust the UI state
            }
        }

        // Handle the case when no song is marked as playing in our system
        if (!$currentQueueSong) {
            // But Spotify is playing something
            if ($playbackData['is_playing'] ?? false) {
                Log::warning("Spotify is playing a track but no song is marked as playing in our system");
                return PlaybackStateManager::PLAYER_STATE_TRACK_MISMATCH;
            }

            // Nothing playing in Spotify either
            return PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK;
        }

        // Continue with your existing logic for when currentQueueSong exists
        // CASE 1: No active playback
        if (empty($playbackData) || !isset($playbackData['item'])) {
            return PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK;
        }

        // CASE 2: Track mismatch
        if ($playbackData['item']['id'] !== $currentQueueSong->song->spotify_id) {
            // Don't report track mismatch if we're in a device change grace period
            if ($this->playbackState->hasDeviceChanged($mix)) {
                Log::info("Ignoring track mismatch during device change grace period. Expected: {$currentQueueSong->song->spotify_id}, playing: {$playbackData['item']['id']}");
                return PlaybackStateManager::PLAYER_STATE_NORMAL; // Continue normal playback
            }

            Log::info("Track mismatch detected. Expected: {$currentQueueSong->song->spotify_id}, playing: {$playbackData['item']['id']}");
            return PlaybackStateManager::PLAYER_STATE_TRACK_MISMATCH;
        }

        // CASE: Paused in Spotify
        if (isset($playbackData['is_playing']) && $playbackData['is_playing'] === false) {
            return PlaybackStateManager::PAUSED;
        }

        // CASE 3: Manual seek detection
        if ($this->isManualSeekToEnd($mix, $previousData, $currentQueueSong)) {
            return PlaybackStateManager::PLAYER_STATE_MANUAL_SEEK_END;
        }

        // CASE 4: Song is near end or finished
        if (isset($playbackData['item']['duration_ms']) && isset($playbackData['progress_ms'])) {
            $durationMs = $playbackData['item']['duration_ms'];
            $progressMs = $playbackData['progress_ms'];
            $remainingMs = $durationMs - $progressMs;
            $percentRemaining = ($remainingMs / $durationMs) * 100;

            // Track ended when progress is very close to duration
            // CRITICAL: Make this more sensitive - lower threshold for more reliable detection
            if ($remainingMs <= 2500 || $percentRemaining <= 1.0) {
                Log::info("Track completion detected - progress at {$progressMs}ms of {$durationMs}ms ({$percentRemaining}% remaining)");
                return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
            }

            // ADDITIONAL CHECK: If previous data exists and shows a different track, that means the track ended
            $current = $playbackData['item']['id'] ?? null;
            $previous = $previousData['item']['id'] ?? null;
            $recentTakeback = $this->playbackState->hasRecentTrackChange($mix);

            if ($current && $previous && $current !== $previous && !$recentTakeback) {
                Log::info("Track change detected from {$previous} to {$current} - treating as track ended");
                return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
            }

            // ADDITIONAL CHECK: If we're in the last 5% of the song and progress is no longer advancing
            if ($percentRemaining <= 5.0 &&
                $previousData &&
                isset($previousData['progress_ms']) &&
                $playbackData['progress_ms'] <= $previousData['progress_ms']) {
                Log::info("Track stalled near end - treating as track ended");
                return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
            }

            // Update song progress in the state manager (for UI and other components)
            $this->playbackState->setSongProgress($mix, $progressMs, $durationMs);

            // CRITICAL: Explicitly set or clear the flag based on the current state
            // This ensures proper invalidation without relying on TTL
            if ($percentRemaining <= 15) {
                // Only set the flag and broadcast if not already done for this track
                if (!$this->playbackState->hasTrackEndNotification($mix, $currentQueueSong->song->spotify_id)) {
                    // Set the flag WITHOUT TTL
                    $this->playbackState->set($mix, PlaybackStateManager::SONG_NEARING_END, true);

                    // Still keep track notification flag
                    $this->playbackState->setTrackEndNotification($mix, $currentQueueSong->song->spotify_id, 20);
                    Log::debug("Song nearing end - {$percentRemaining}% remaining");

                    // Check if this is the last song in the queue
                    $hasMoreSongs = QueueSong::where('mix_id', $mix->id)
                        ->where('status', 'pending')
                        ->exists();

                    if (!$hasMoreSongs && $remainingMs <= 5000) {
                        return PlaybackStateManager::QUEUE_COMPLETED;
                    }

                    // Only return nearing_end if we're not too close to the end
                    if ($remainingMs > 3000) {
                        return PlaybackStateManager::SONG_NEARING_END;
                    }
                }
            } else {
                // EXPLICITLY CLEAR the flag when no longer nearing end
                // This ensures proper cache invalidation without relying on TTL
                $this->playbackState->forget($mix, PlaybackStateManager::SONG_NEARING_END);
            }
        }

        // CASE 5: Playback is stuck
        if ($this->isPlaybackStuck($previousData, $playbackData)) {
            return PlaybackStateManager::PLAYER_STATE_STUCK;
        }

        // Default: playback is normal
        return PlaybackStateManager::PLAYER_STATE_NORMAL;
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

        // Large forward jump to near the end
        if ($progressDiff > 5000 && ($currentPosition / $durationMs) > 0.9) {
            Log::info("Detected manual seek to near end");
            $this->playbackState->setSeekDetected($mix, $currentQueueSong->id, true);
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

                // Set cache flag
                $this->playbackState->setQueueCompleted($mix, true);

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
