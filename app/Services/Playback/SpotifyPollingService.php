<?php

namespace App\Services\Playback;

use App\Models\Mix;
use App\Models\QueueSong;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;
use App\Services\Spotify\SpotifyService;
use App\Services\Queue\QueueManagementService;
use App\Events\DeviceUpdatedEvent;
use Exception;

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
            $currentQueueSong = QueueSong::currentlyPlayingForMix($mix);

            if ($playbackData === null) {
                // Broadcast device inactive event
                DeviceUpdatedEvent::dispatch($mix);

                // Set a device inactive flag on the playback state
                $this->playbackState->setState($mix, 'device_inactive', true);

                return [
                    'success' => false,
                    'action' => 'device_inactive',
                    'message' => 'Spotify device is inactive or unavailable'
                ];
            }

            // Analyze and handle the current state
            $playerState = $this->analyzePlayerState($mix, $currentQueueSong, $playbackData, $previousData);
            $actionResult = $this->handlePlayerState($mix, $playerState, $playbackData, $currentQueueSong, $previousData);

            // Check if we need to extend queue
            $this->songPlaybackService->extendQueueIfNeeded($mix);

            return $actionResult ?? ['success' => true, 'action' => 'continue'];

        } catch (Exception $e) {
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

        // Enhanced track end detection
        if (isset($playbackData['item']['duration_ms']) && isset($playbackData['progress_ms'])) {
            $durationMs = $playbackData['item']['duration_ms'];
            $progressMs = $playbackData['progress_ms'];

            // Track is at 90% or more of its duration (lower threshold)
            if ($durationMs > 0 && ($progressMs / $durationMs) >= 0.90) {
                // If we're near end AND progress hasn't changed significantly
                if ($previousData &&
                    isset($previousData['progress_ms']) &&
                    abs($progressMs - $previousData['progress_ms']) < 500 &&
                    $progressMs > 5000) { // Make sure we're not just starting


                    // If we're VERY close to end (>97%) OR stalled for multiple polls
                    if (($progressMs / $durationMs) >= 0.97 ||
                        $this->playbackState->hasState($mix, 'progress_stalled')) {

                        Log::info("Detected track ended for mix {$mix->id} (progress: {$progressMs}/{$durationMs})");
                        return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
                    } else {
                        // Mark stalled progress for next poll
                        $this->playbackState->setState($mix, 'progress_stalled', true);
                    }
                } else {
                    // Progress is still advancing, clear stalled state
                    $this->playbackState->forgetState($mix, 'progress_stalled');
                }
            }
        }

        // Loop Detection - Check this before any other playback analysis
        if (isset($previousData['item']['id']) &&
            isset($playbackData['item']['id']) &&
            isset($previousData['progress_ms']) &&
            isset($playbackData['progress_ms'])) {

            // If same track ID but progress reset to beginning
            if ($previousData['item']['id'] === $playbackData['item']['id'] &&
                $previousData['progress_ms'] > 5000 && // Wasn't already at start
                $playbackData['progress_ms'] < 3000) { // Now is at start

                Log::info("Detected track reset to beginning for mix {$mix->id}, treating as track ended");
                return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
            }
        }

        // Check for tracks that might be about to repeat due to reaching their end
        if (isset($playbackData['item']['id']) &&
            isset($playbackData['item']['duration_ms']) &&
            isset($playbackData['progress_ms'])) {

            $durationMs = $playbackData['item']['duration_ms'];
            $progressMs = $playbackData['progress_ms'];

            // If we're at 98%+ of the song, just consider it ended
            if (($progressMs / $durationMs) >= 0.98) {
                Log::info("Detected track at very end for mix {$mix->id} ({$progressMs}/{$durationMs}), treating as track ended");
                return PlaybackStateManager::PLAYER_STATE_TRACK_ENDED;
            }
        }

        // Default: normal playback
        return PlaybackStateManager::PLAYER_STATE_NORMAL;
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
        // Get current queue song
        $currentQueueSong = QueueSong::currentlyPlayingForMix($mix);

        // Get playback data
        $playbackData = $this->playbackState->getPlaybackData($mix);

        // ALWAYS CHECK FOR LOOPS FIRST - regardless of any other state
        if ($currentQueueSong && $playbackData &&
            isset($playbackData['item']['id']) &&
            $playbackData['item']['id'] === $currentQueueSong->song->spotify_id &&
            isset($playbackData['progress_ms']) &&
            $playbackData['progress_ms'] < 3000) {

            // This is definitely a loop back to beginning
            Log::info("Detected track looped back to beginning for mix {$mix->id}, treating as track ended - IGNORING DEVICE CHANGE");
            return $this->songPlaybackService->advanceToNextSong($mix);
        }

        // Only then check device change
        if ($this->playbackState->hasDeviceChanged($mix)) {
            Log::info("Track mismatch detected but ignoring due to recent device change for mix {$mix->id}");
            return null;
        }

        // Otherwise resume intended track
        Log::info("Detected track mismatch for mix {$mix->id}, resuming intended track");
        return $this->songPlaybackService->resumeIntendedTrack($mix);
    }

    /**
     * Handle no playback state
     */
    private function handleNoPlayback(Mix $mix): ?array
    {
        // If we know a song should be playing but nothing is playing
        if (QueueSong::currentlyPlayingForMix($mix)) {
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
