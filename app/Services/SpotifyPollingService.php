<?php

namespace App\Services;

use App\Models\Mix;
use App\Models\QueueSong;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\PlaybackDataUpdatedEvent;

class SpotifyPollingService
{
    // Cache key prefixes for consistency
    private const CACHE_PREFIX_PLAYBACK = 'mix:playback:';
    private const CACHE_PREFIX_SEEK = 'mix:seek:';
    private const CACHE_PREFIX_ENDING = 'mix:ending:';
    private const CACHE_PREFIX_STUCK = 'mix:stuck:';

    // Define player state constants
    public const PLAYER_STATE_NORMAL = 'normal';
    public const PLAYER_STATE_NO_PLAYBACK = 'no_playback';
    public const PLAYER_STATE_TRACK_MISMATCH = 'track_mismatch';
    public const PLAYER_STATE_NEARING_END = 'nearing_end';
    public const PLAYER_STATE_FINISHED = 'finished';
    public const PLAYER_STATE_STUCK = 'stuck';
    public const PLAYER_STATE_MANUAL_SEEK_END = 'manual_seek_end';
    public const PLAYER_STATE_PLAYING_TOO_LONG = 'playing_too_long';
    public const PLAYER_STATE_QUEUE_COMPLETED = 'queue_completed';
    public const PLAYER_STATE_TRACK_ENDED = 'track_ended';

    protected $changeReason = '';

    public function __construct(
        protected SpotifyService $spotifyService,
        protected SongPlaybackService $songPlaybackService,
        protected QueueManagementService $queueManagementService
    ) {
    }

    /**
     * Poll Spotify for the current playback state
     */
    public function pollPlayback(Mix $mix): void
    {
        $cacheKey = self::CACHE_PREFIX_PLAYBACK . $mix->id;

        try {
            // Get the mix owner
            $user = User::find($mix->user_id);

            // Check if we recently changed devices - if so, skip this poll cycle
            if (Cache::has("mix:{$mix->id}:device_changed")) {
                Log::info("Mix {$mix->id} was just manually changed, skipping this poll");
                return;
            }

            // Check for cached device ID - for awareness only, not for changing
            $selectedDeviceId = Cache::get("mix:{$mix->id}:device_id");

            // Get current playback data from Spotify
            $playbackData = $this->spotifyService->getCurrentPlayback($user);

            // Store previous playback data for comparison
            $previousData = Cache::get($cacheKey);

            // No active playback detected
            if (!$playbackData) {
                // Handle no playback case
                $this->handlePlayerState($mix, self::PLAYER_STATE_NO_PLAYBACK);

                // Use the updateCacheAndBroadcast method to handle no playback data
                $this->updateCacheAndBroadcast($mix, null, $previousData, $cacheKey);
                return;
            }

            // Store the playback data in cache
            Cache::put($cacheKey, $playbackData, now()->addMinutes(5));

            // Get the currently playing song according to our queue
            $currentQueueSong = $this->songPlaybackService->getCurrentlyPlayingSong($mix->id);

            // Analyze the current playback state
            $playerState = $this->analyzePlayerState($mix, $user, $currentQueueSong, $playbackData, $previousData);

            // When device mismatch is detected:
            if ($selectedDeviceId && isset($playbackData['device']['id']) &&
                $playbackData['device']['id'] !== $selectedDeviceId) {

                // IMPORTANT: Don't automatically switch devices, just log the issue
                Log::warning("Device mismatch detected during polling. Selected: {$selectedDeviceId}, Active: {$playbackData['device']['id']}");

                // We still broadcast the current playback state, but add a flag
                $playbackData['_device_mismatch'] = true;

                // Only if we're not in a device change grace period
                if (!Cache::has("mix:{$mix->id}:device_changed")) {
                    // Set a flag that will be used by the frontend to display a device mismatch warning
                    Cache::put("mix:{$mix->id}:device_mismatch", true, now()->addMinutes(5));
                }
            }

            // Track mismatch is a separate issue from device mismatch
            if ($playerState === self::PLAYER_STATE_TRACK_MISMATCH) {
                // Handle mismatch by correcting playback, BUT preserve device ID
                $this->handlePlayerState($mix, $playerState);

                // Don't broadcast mismatched playback data - we'll get a new event when it's fixed
                Log::info("Suppressing playback data broadcast due to track mismatch");
                return;
            }

            // For regular playback changes, handle them normally
            if ($playerState !== self::PLAYER_STATE_NORMAL) {
                $this->handlePlayerState($mix, $playerState);
            }

            // Use your existing method to update cache and broadcast only significant changes
            $this->updateCacheAndBroadcast($mix, $playbackData, $previousData, $cacheKey);
        } catch (\Exception $e) {
            Log::error("Error polling Spotify: " . $e->getMessage());
        }
    }

    /**
     * Analyze the player state based on current and previous playback data
     * Simplified version focusing on the most common scenarios
     */
    private function analyzePlayerState(
        Mix $mix,
        User $user,
        QueueSong $currentQueueSong,
        ?array $playbackData,
        ?array $previousData
    ): string {
        // CASE 1: No active playback
        if (empty($playbackData) || !isset($playbackData['item'])) {
            return self::PLAYER_STATE_NO_PLAYBACK;
        }

        // CASE 2: Track mismatch
        if ($playbackData['item']['id'] !== $currentQueueSong->song->spotify_id) {
            Log::info("Track mismatch detected. Expected: {$currentQueueSong->song->spotify_id}, playing: {$playbackData['item']['id']}");
            return self::PLAYER_STATE_TRACK_MISMATCH;
        }

        // CASE 3: Manual seek detection
        if ($this->isManualSeekToEnd($previousData, $playbackData, $currentQueueSong)) {
            return self::PLAYER_STATE_MANUAL_SEEK_END;
        }

        // CASE 4: Song is near end or finished
        if (isset($playbackData['item']['duration_ms']) && isset($playbackData['progress_ms'])) {
            $durationMs = $playbackData['item']['duration_ms'];
            $progressMs = $playbackData['progress_ms'];
            $remainingMs = $durationMs - $progressMs;
            $percentRemaining = ($remainingMs / $durationMs) * 100;

            // Set flag for tighter polling when nearing end
            if ($percentRemaining <= 15) {
                $songNearingEndKey = self::CACHE_PREFIX_ENDING . $mix->id;
                Cache::put($songNearingEndKey, true, now()->addSeconds(20));
                Log::debug("Song nearing end - {$percentRemaining}% remaining");

                // Only return nearing_end if not past the threshold for finished
                if ($remainingMs > 3000) {
                    return self::PLAYER_STATE_NEARING_END;
                }
            }

            // Song is finished or very close to ending
            if ($remainingMs <= 3000) {
                return self::PLAYER_STATE_FINISHED;
            }
        }

        // CASE 5: Playback is stuck
        if ($this->isPlaybackStuck($previousData, $playbackData)) {
            return self::PLAYER_STATE_STUCK;
        }

        // Default: playback is normal
        return self::PLAYER_STATE_NORMAL;
    }

    /**
     * Check if user manually sought to near the end of the track
     */
    private function isManualSeekToEnd(?array $previousData, ?array $playbackData, QueueSong $currentQueueSong): bool
    {
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
            Cache::put(self::CACHE_PREFIX_SEEK . $currentQueueSong->id, true, now()->addSeconds(15));
        }

        // Already detected seek and now very close to the end
        return Cache::has(self::CACHE_PREFIX_SEEK . $currentQueueSong->id) &&
               ($currentPosition / $durationMs) > 0.97;
    }

    /**
     * Check if playback appears to be stuck
     */
    private function isPlaybackStuck(?array $previousData, ?array $playbackData): bool
    {
        if (!$previousData ||
            !isset($previousData['progress_ms']) ||
            !isset($playbackData['progress_ms']) ||
            !isset($previousData['item']['id']) ||
            $previousData['item']['id'] !== $playbackData['item']['id']) {
            return false;
        }

        $progressDiff = abs($previousData['progress_ms'] - $playbackData['progress_ms']);

        // Progress is stuck or barely changed
        return $progressDiff < 500;
    }

    /**
     * Handle the player state based on analysis
     */
    private function handlePlayerState(Mix $mix, string $playerState): void
    {
        // Try harder to find device ID - check multiple patterns
        $deviceId = Cache::get("mix:{$mix->id}:device_id");

        if (!$deviceId) {
            // Try to get device ID from the current playback data
            $cacheKey = self::CACHE_PREFIX_PLAYBACK . $mix->id;
            $playbackData = Cache::get($cacheKey);

            if ($playbackData && isset($playbackData['device']['id'])) {
                $deviceId = $playbackData['device']['id'];
                // Save it for future use
                Cache::put("mix:{$mix->id}:device_id", $deviceId, now()->addHours(1));
                Log::info("Retrieved device ID {$deviceId} from current playback and saved to cache");
            }
        }

        Log::info("Handling player state {$playerState} for mix {$mix->id}" .
                  ($deviceId ? " with device {$deviceId}" : " with no specific device"));

        switch ($playerState) {
            case self::PLAYER_STATE_TRACK_ENDED:
            case self::PLAYER_STATE_FINISHED:
                Log::info("Detected track ended for mix {$mix->id}, advancing to next song");
                // Use the SongPlaybackService instead of QueueManagementService
                $this->songPlaybackService->advanceToNextSong($mix->id);
                break;

            case self::PLAYER_STATE_TRACK_MISMATCH:
                // Only try to fix mismatches if we're not in a device change grace period
                if (!Cache::has("mix:{$mix->id}:device_changed")) {
                    Log::info("Detected track mismatch for mix {$mix->id}, resuming intended track");
                    $this->songPlaybackService->resumeIntendedTrack($mix->id);
                } else {
                    Log::info("Track mismatch detected but ignoring due to recent device change for mix {$mix->id}");
                }
                break;

            case self::PLAYER_STATE_STUCK:
                Log::info("Detected stuck playback for mix {$mix->id}, resuming playback");
                $this->songPlaybackService->resumeIntendedTrack($mix->id);
                break;

            case self::PLAYER_STATE_MANUAL_SEEK_END:
                Log::info("Detected manual seek to end for mix {$mix->id}, advancing to next song");
                $this->songPlaybackService->advanceToNextSong($mix->id);
                break;

            case self::PLAYER_STATE_NO_PLAYBACK:
                // If we know a song should be playing but nothing is playing
                if ($this->songPlaybackService->getCurrentlyPlayingSong($mix->id)) {
                    Log::info("No playback detected but song should be playing for mix {$mix->id}, resuming playback");
                    $this->songPlaybackService->resumeIntendedTrack($mix->id);
                }
                break;
        }
    }

    /**
     * Update cache and broadcast player data
     */
    private function updateCacheAndBroadcast(Mix $mix, ?array $playbackData, ?array $previousData, string $cacheKey): void
    {
        // Handle no playback data case
        if (empty($playbackData) || !isset($playbackData['item'])) {
            $noPlaybackData = [
                'status' => 'no_active_playback',
                '_timestamp' => now()->timestamp
            ];

            Cache::put($cacheKey, $noPlaybackData);

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
        Cache::put($cacheKey, $playbackData);

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
    private function hasSignificantChanges($previous, $current): bool
    {
        if (!$previous) {
            $this->changeReason = "first broadcast";
            return true;
        }

        // Check for play state change
        if (($previous['is_playing'] ?? false) !== ($current['is_playing'] ?? false)) {
            $this->changeReason = "play state changed";
            return true;
        }

        // Check for track change
        if (($previous['item']['id'] ?? null) !== ($current['item']['id'] ?? null)) {
            $this->changeReason = "track changed";
            return true;
        }

        // Not significant enough to broadcast
        return false;
    }
}
