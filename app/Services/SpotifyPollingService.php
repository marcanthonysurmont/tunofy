<?php

namespace App\Services;

use App\Models\Mix;
use App\Models\QueueSong;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\MixStatusChangedEvent;

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

    protected $changeReason = '';

    public function __construct(
        protected SpotifyService $spotifyService,
        protected SongPlaybackService $songPlaybackService
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

            // Get current playback data from Spotify
            $playbackData = $this->spotifyService->getCurrentPlayback($user);

            // Get previous playback data from cache
            $previousData = Cache::get($cacheKey);

            // Get current song from our queue
            $currentQueueSong = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'playing')
                ->with('song')
                ->first();

            // Check for pending songs if nothing is playing
            if (!$currentQueueSong) {
                $pendingSongs = QueueSong::where('mix_id', $mix->id)
                    ->where('status', 'pending')
                    ->where('is_killed', false)
                    ->exists();

                if ($pendingSongs) {
                    $this->songPlaybackService->startPlayback($mix->id);

                    // After starting playback, refresh playback data
                    $playbackData = $this->spotifyService->getCurrentPlayback($user);
                } else {
                    // No current song playing and no pending songs - check if queue is complete
                    $anyPlayedSongs = QueueSong::where('mix_id', $mix->id)
                        ->whereIn('status', ['finished', 'interrupted'])
                        ->exists();

                    if ($anyPlayedSongs) {
                        // Queue is complete - pause playback and deactivate mix
                        Log::info("Queue completed for mix {$mix->id} - auto-deactivating");

                        // Pause playback first
                        $this->spotifyService->pausePlayback($user);

                        // Deactivate the mix
                        $mix->is_active = false;
                        $mix->save();

                        // Broadcast completion status
                        event(new MixStatusChangedEvent($mix, false));
                        $this->updateCacheAndBroadcast($mix, [
                            'status' => 'queue_completed',
                            '_timestamp' => now()->timestamp
                        ], null, $cacheKey);

                        // Exit immediately
                        return;
                    }
                }
            } else {
                // Analyze player state and take appropriate action
                $playerState = $this->analyzePlayerState(
                    $mix,
                    $user,
                    $currentQueueSong,
                    $playbackData,
                    $previousData
                );

                // If track mismatch is detected
                if ($playerState === self::PLAYER_STATE_TRACK_MISMATCH) {
                    // Handle mismatch by correcting playback
                    $this->handlePlayerState($mix, $playerState);

                    // Don't broadcast mismatched playback data - we'll get a new event when it's fixed
                    Log::info("Suppressing playback data broadcast due to track mismatch");
                    return;
                }

                $stateChanged = $this->handlePlayerState($mix, $playerState);

                // If state changed (song advanced), refresh playback data
                if ($stateChanged && in_array($playerState, [
                    self::PLAYER_STATE_FINISHED,
                    self::PLAYER_STATE_TRACK_MISMATCH,
                    self::PLAYER_STATE_NO_PLAYBACK,
                    self::PLAYER_STATE_MANUAL_SEEK_END,
                    self::PLAYER_STATE_PLAYING_TOO_LONG
                ])) {
                    // Give Spotify a moment to update
                    sleep(1);

                    // Refresh playback data
                    $playbackData = $this->spotifyService->getCurrentPlayback($user);
                }
            }

            // Always update cache and broadcast
            $this->updateCacheAndBroadcast($mix, $playbackData, $previousData, $cacheKey);

        } catch (\Exception $e) {
            Log::error("Error polling playback for mix {$mix->id}: " . $e->getMessage());
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
     * Take action based on the analyzed player state
     */
    private function handlePlayerState(Mix $mix, string $playerState): bool
    {
        $stateChanged = false;
        $resumeOnMismatch = false;

        switch ($playerState) {
            case self::PLAYER_STATE_NO_PLAYBACK:
            case self::PLAYER_STATE_TRACK_MISMATCH:
            case self::PLAYER_STATE_FINISHED:
            case self::PLAYER_STATE_MANUAL_SEEK_END:
            case self::PLAYER_STATE_PLAYING_TOO_LONG:
                // Clear cache before taking action
                $playbackCacheKey = self::CACHE_PREFIX_PLAYBACK . $mix->id;
                Cache::forget($playbackCacheKey);
                Log::info("Cleared playback cache for mix {$mix->id}");

                // For track mismatch, consider resuming intended track
                if ($playerState === self::PLAYER_STATE_TRACK_MISMATCH && $resumeOnMismatch) {
                    // Try to resume our intended track
                    $this->songPlaybackService->resumeIntendedTrack($mix->id);
                } else {
                    // Otherwise advance to next song
                    $this->songPlaybackService->advanceToNextSong($mix->id);
                }

                $stateChanged = true;
                break;

            case self::PLAYER_STATE_STUCK:
                // Increment stuck counter and advance if stuck for too long
                $stuckKey = self::CACHE_PREFIX_STUCK . $mix->id;
                $stuckCount = Cache::increment($stuckKey, 1, 3600);

                if ($stuckCount >= 3) {
                    Log::info("Playback stuck for mix {$mix->id} - advancing queue");

                    // IMPORTANT: Also clear cache here
                    $playbackCacheKey = self::CACHE_PREFIX_PLAYBACK . $mix->id;
                    Cache::forget($playbackCacheKey);

                    $this->songPlaybackService->advanceToNextSong($mix->id);
                    Cache::forget($stuckKey);
                    $stateChanged = true;
                }
                break;

            case self::PLAYER_STATE_NEARING_END:
                // Just log and continue - we've already set the cache flag
                // for tighter polling in analyzePlayerState
                Log::debug("Song nearing end for mix {$mix->id} - monitoring closely");
                break;
        }

        return $stateChanged;
    }

    /**
     * Update cache and broadcast player data
     */
    private function updateCacheAndBroadcast(Mix $mix, ?array $playbackData, ?array $previousData, string $cacheKey): void
    {
        // Handle no playback data
        if (empty($playbackData) || !isset($playbackData['item'])) {
            $noPlaybackData = [
                'status' => 'no_active_playback',
                '_timestamp' => now()->timestamp
            ];

            Cache::put($cacheKey, $noPlaybackData);

            // Always broadcast no playback state
            Log::info("Broadcasting no active playback for mix {$mix->id}");
            event(new PlaybackDataUpdatedEvent($mix, $noPlaybackData));
            return;
        }

        // Add timestamp to playback data
        $playbackData['_timestamp'] = now()->timestamp;

        // Update cache
        Cache::put($cacheKey, $playbackData);

        // MODIFICATION: Always broadcast when the mix is active regardless of changes
        if ($mix->is_active) {
            // For active mixes, broadcast every update to keep all browsers in sync
            Log::info("Broadcasting playback data for active mix {$mix->id}");
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));
            return;
        }

        // For inactive mixes, only broadcast significant changes
        if ($this->hasSignificantChanges($previousData, $playbackData)) {
            Log::info("Broadcasting playback change: {$this->changeReason}");
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));
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
