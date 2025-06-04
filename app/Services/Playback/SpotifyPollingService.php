<?php

namespace App\Services\Playback;

use App\Models\Mix;
use App\Models\QueueSong;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;
use App\Services\Spotify\SpotifyService;
use App\Services\Queue\QueueManagementService;

class SpotifyPollingService
{
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
            // Record the poll start time for reference
            $pollStartTime = now()->timestamp;
            $this->playbackState->set($mix, 'poll_start_time', $pollStartTime);

            // Handle special states first with early returns
            if ($this->playbackState->isQueueCompleted($mix)) {
                return ['success' => false, 'action' => 'stop_polling', 'message' => 'Queue completed'];
            }

            if ($this->playbackState->hasDeviceFailure($mix)) {
                return ['success' => false, 'action' => 'waiting_for_device', 'message' => 'No device available'];
            }

            // Get the controlling user
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

            // Get and store Spotify playback data
            $playbackData = $this->spotifyService->getCurrentPlayback($user);
            $previousData = $this->playbackState->getPlaybackData($mix);

            // Always atomically update the cache with current data
            $this->playbackState->setPlaybackData($mix, $playbackData ?: ['status' => 'no_playback', 'timestamp' => time()]);

            // Get current queue state
            $currentQueueSong = $this->songPlaybackService->getCurrentlyPlayingSong($mix);

            // Analyze and handle the current state
            $playerState = $this->analyzePlayerState($mix, $currentQueueSong, $playbackData, $previousData);
            $actionResult = $this->handlePlayerState($mix, $playerState, $playbackData, $currentQueueSong, $previousData);

            // Only broadcast if there are significant changes
            if ($this->hasSignificantChanges($previousData, $playbackData)) {
                event(new PlaybackDataUpdatedEvent($mix, $playbackData ?: ['status' => 'no_playback']));
            }

            // Check if we need to extend queue
            $this->songPlaybackService->extendQueueIfNeeded($mix);

            return $actionResult ?? ['success' => true, 'action' => 'continue'];

        } catch (\Exception $e) {
            Log::error("Error polling Spotify: " . $e->getMessage(), ['exception' => $e]);
            return ['success' => false, 'action' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Analyze the current player state
     */
    private function analyzePlayerState(Mix $mix, ?QueueSong $currentQueueSong, array $playbackData, ?array $previousData = null): string
    {
        // Guard conditions (most important checks first)
        if (!$mix->is_active) {
            return PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK;
        }

        // No playback data means no active playback
        if (empty($playbackData) || !isset($playbackData['item'])) {
            return PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK;
        }

        // No current song in our database
        if (!$currentQueueSong) {
            return PlaybackStateManager::PLAYER_STATE_TRACK_MISMATCH;
        }

        // Check if paused
        if (!$playbackData['is_playing']) {
            return PlaybackStateManager::PAUSED;
        }

        // Track mismatch check (different song playing than expected)
        if ($playbackData['item']['id'] !== $currentQueueSong->song->spotify_id) {
            return PlaybackStateManager::PLAYER_STATE_TRACK_MISMATCH;
        }

        // Check if track has ended
        if (isset($playbackData['item']['duration_ms']) && isset($playbackData['progress_ms'])) {
            $durationMs = $playbackData['item']['duration_ms'];
            $progressMs = $playbackData['progress_ms'];

            // Track is at 97% or more of its duration
            if ($durationMs > 0 && ($progressMs / $durationMs) >= 0.97) {
                return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
            }
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
        // Don't handle player state for inactive mixes
        if (!$mix->is_active && $playerState !== PlaybackStateManager::QUEUE_COMPLETED) {
            return null;
        }

        Log::info("Handling player state {$playerState} for mix {$mix->id}");

        // Handle the most common cases first with cleaner logic
        switch ($playerState) {
            case PlaybackStateManager::PLAYER_STATE_TRACK_ENDED:
                return $this->handleTrackEnded($mix, $currentQueueSong);

            case PlaybackStateManager::PLAYER_STATE_TRACK_MISMATCH:
                return $this->handleTrackMismatch($mix);

            case PlaybackStateManager::PLAYER_STATE_NO_PLAYBACK:
                return $this->handleNoPlayback($mix);

            case PlaybackStateManager::PAUSED:
                // Just update cache and broadcast if needed
                $this->updateCacheAndBroadcast($mix, $playbackData, $previousData);
                return null;

            case PlaybackStateManager::PLAYER_STATE_NORMAL:
                // Just update cache and broadcast if needed
                $this->updateCacheAndBroadcast($mix, $playbackData, $previousData);
                return null;
        }

        return null;
    }

    /**
     * Handle track ended state
     */
    private function handleTrackEnded(Mix $mix, ?QueueSong $currentQueueSong): ?array
    {
        Log::info("Detected track ended for mix {$mix->id}, advancing to next song");

        if ($currentQueueSong) {
            $this->playbackState->clearTrackEndNotification($mix, $currentQueueSong->song->spotify_id);
        }

        // Advance to next song
        return $this->songPlaybackService->advanceToNextSong($mix);
    }

    /**
     * Handle track mismatch state
     */
    private function handleTrackMismatch(Mix $mix): ?array
    {
        // Only try to fix mismatches if we're not in a device change grace period
        if (!$this->playbackState->hasDeviceChanged($mix)) {
            Log::info("Detected track mismatch for mix {$mix->id}, resuming intended track");
            return $this->songPlaybackService->resumeIntendedTrack($mix);
        } else {
            Log::info("Track mismatch detected but ignoring due to recent device change for mix {$mix->id}");
        }
        return null;
    }

    /**
     * Handle no playback state
     */
    private function handleNoPlayback(Mix $mix): ?array
    {
        // If we know a song should be playing but nothing is playing
        if ($this->songPlaybackService->getCurrentlyPlayingSong($mix)) {
            Log::info("No playback detected but song should be playing for mix {$mix->id}, resuming playback");
            return $this->songPlaybackService->resumeIntendedTrack($mix);
        }
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
