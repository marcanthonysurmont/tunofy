<?php

namespace App\Services;

use App\Events\DeviceUpdatedEvent;
use App\Models\Mix;
use App\Models\QueueSong;
use App\Models\User;
use App\Models\PlaybackSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\MixStatusChangedEvent;
use App\Events\CoDJUpdatedEvent;

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
    public const PLAYER_STATE_PAUSED = 'paused';

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
    public function pollPlayback(Mix $mix)
    {
        $cacheKey = self::CACHE_PREFIX_PLAYBACK . $mix->id;

        try {
            // Check if the queue has been completed - skip polling entirely
            $playbackState = app(PlaybackStateManager::class);
            if ($playbackState->isQueueCompleted($mix)) {
                Log::info("Mix {$mix->id} queue completed, skipping polling");
                return;
            }

            if (Cache::has("mix:{$mix->id}:device_failure")) {
                // event(new DeviceUpdatedEvent($mix));

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
            if ($playbackState->has($mix, PlaybackStateManager::DEVICE_CHANGED)) {
                Log::info("Mix {$mix->id} was just manually changed, skipping this poll");
                // Consume the flag after using it
                $playbackState->forget($mix, PlaybackStateManager::DEVICE_CHANGED);
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
            $currentQueueSong = $this->songPlaybackService->getCurrentlyPlayingSong($mix);

            // Analyze the current playback state
            $playerState = $this->analyzePlayerState($mix, $user, $currentQueueSong, $playbackData, $previousData);

            // Handle the player state - now with return value
            $result = $this->handlePlayerState($mix, $playerState, $playbackData, $currentQueueSong, $previousData);

            // Check for queue completion signal
            if ($result === self::PLAYER_STATE_QUEUE_COMPLETED) {
                // Set the queue_completed flag in cache
                $playbackState->setQueueCompleted($mix, true);
                return "stop_polling";
            }

            return true; // Normal return
        } catch (\Exception $e) {
            Log::error("Error polling Spotify: " . $e->getMessage());
        }
    }

    /**
     * Analyze the current player state
     */
    private function analyzePlayerState(Mix $mix, User $user, ?QueueSong $currentQueueSong, array $playbackData, ?array $previousData = null): string
    {
        // Handle the case when no song is marked as playing in our system
        if (!$currentQueueSong) {
            // But Spotify is playing something
            if ($playbackData['is_playing'] ?? false) {
                Log::warning("Spotify is playing a track but no song is marked as playing in our system");
                return self::PLAYER_STATE_TRACK_MISMATCH;
            }

            // Nothing playing in Spotify either
            return self::PLAYER_STATE_NO_PLAYBACK;
        }

        // Continue with your existing logic for when currentQueueSong exists
        // CASE 1: No active playback
        if (empty($playbackData) || !isset($playbackData['item'])) {
            return self::PLAYER_STATE_NO_PLAYBACK;
        }

        // CASE 2: Track mismatch
        if ($playbackData['item']['id'] !== $currentQueueSong->song->spotify_id) {
            Log::info("Track mismatch detected. Expected: {$currentQueueSong->song->spotify_id}, playing: {$playbackData['item']['id']}");
            return self::PLAYER_STATE_TRACK_MISMATCH;
        }

        // CASE: Paused in Spotify
        if (isset($playbackData['is_playing']) && $playbackData['is_playing'] === false) {
            return self::PLAYER_STATE_PAUSED;
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

            // Track ended when progress is very close to duration
            // CRITICAL: Make this more sensitive - lower threshold for more reliable detection
            if ($remainingMs <= 2500 || $percentRemaining <= 1.0) {
                Log::info("Track completion detected - progress at {$progressMs}ms of {$durationMs}ms ({$percentRemaining}% remaining)");
                return self::PLAYER_STATE_TRACK_ENDED;
            }

            // Get playback state manager
            $playbackState = app(PlaybackStateManager::class);

            // Update song progress in the state manager (for UI and other components)
            $playbackState->setSongProgress($mix, $progressMs, $durationMs);

            // CRITICAL: Explicitly set or clear the flag based on the current state
            // This ensures proper invalidation without relying on TTL
            if ($percentRemaining <= 15) {
                // Only set the flag and broadcast if not already done for this track
                $trackEndNotifiedKey = "mix:{$mix->id}:track_end_notified:{$currentQueueSong->song->spotify_id}";
                if (!Cache::has($trackEndNotifiedKey)) {
                    // Set the flag WITHOUT TTL
                    $playbackState->set($mix, PlaybackStateManager::SONG_NEARING_END, true);

                    // Still keep track notification flag
                    Cache::put($trackEndNotifiedKey, true, now()->addSeconds(20));
                    Log::debug("Song nearing end - {$percentRemaining}% remaining");

                    // Check if this is the last song in the queue
                    $hasMoreSongs = QueueSong::where('mix_id', $mix->id)
                        ->where('status', 'pending')
                        ->exists();

                    if (!$hasMoreSongs && $remainingMs <= 5000) {
                        return self::PLAYER_STATE_QUEUE_COMPLETED;
                    }

                    // Only return nearing_end if we're not too close to the end
                    if ($remainingMs > 3000) {
                        return self::PLAYER_STATE_NEARING_END;
                    }
                }
            } else {
                // EXPLICITLY CLEAR the flag when no longer nearing end
                // This ensures proper cache invalidation without relying on TTL
                $playbackState->forget($mix, PlaybackStateManager::SONG_NEARING_END);
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
            case self::PLAYER_STATE_QUEUE_COMPLETED:
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
                $playbackState = app(PlaybackStateManager::class);
                $playbackState->setQueueCompleted($mix, true);

                // *** IMPORTANT: Mark the mix itself as inactive ***
                $mix->update(['is_active' => false]);
                Log::info("Marked mix {$mix->id} as inactive after queue completion");

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

                // Broadcast queue completion AND deactivation
                event(new MixStatusChangedEvent(
                    $mix,
                    false,  // Important: Set to false to indicate mix is now inactive
                    'queue_completed'
                ));

                return self::PLAYER_STATE_QUEUE_COMPLETED;
                break;

            case self::PLAYER_STATE_TRACK_ENDED:
                Log::info("Detected track ended for mix {$mix->id}, advancing to next song");

                // Clear notification flags
                if (isset($currentQueueSong)) {
                    Cache::forget("mix:{$mix->id}:track_end_notified:{$currentQueueSong->song->spotify_id}");
                }

                // Clear nearing end flag
                $playbackState = app(PlaybackStateManager::class);
                $playbackState->forget($mix, PlaybackStateManager::SONG_NEARING_END);

                // Explicitly log advancement attempt
                Log::info("Attempting to advance to next song for mix {$mix->id}");

                // Advance to next song
                $result = $this->songPlaybackService->advanceToNextSong($mix);

                // Log the result for debugging
                Log::info("Advance result: " . json_encode($result));
                break;

            case self::PLAYER_STATE_TRACK_MISMATCH:
                // Only try to fix mismatches if we're not in a device change grace period
                if (!Cache::has("mix:{$mix->id}:device_changed")) {
                    Log::info("Detected track mismatch for mix {$mix->id}, resuming intended track");
                    $this->songPlaybackService->resumeIntendedTrack($mix);
                } else {
                    Log::info("Track mismatch detected but ignoring due to recent device change for mix {$mix->id}");
                }
                break;

            case self::PLAYER_STATE_STUCK:
                Log::info("Detected stuck playback for mix {$mix->id}, resuming playback");
                $this->songPlaybackService->resumeIntendedTrack($mix);
                break;

            case self::PLAYER_STATE_MANUAL_SEEK_END:
                Log::info("Detected manual seek to end for mix {$mix->id}, advancing to next song");
                $this->songPlaybackService->advanceToNextSong($mix);
                break;

            case self::PLAYER_STATE_NO_PLAYBACK:
                // If we know a song should be playing but nothing is playing
                if ($this->songPlaybackService->getCurrentlyPlayingSong($mix)) {
                    Log::info("No playback detected but song should be playing for mix {$mix->id}, resuming playback");
                    $this->songPlaybackService->resumeIntendedTrack($mix);
                }
                break;

            case self::PLAYER_STATE_PAUSED:
                Log::info("Detected paused playback for mix {$mix->id}, broadcasting pause event");

                if ($this->hasSignificantChanges($previousData, $playbackData)) {
                    event(new PlaybackDataUpdatedEvent($mix, $playbackData));
                } else {
                    Log::debug("Paused state unchanged for mix {$mix->id}, skipping broadcast");
                }
                break;

            case self::PLAYER_STATE_NORMAL:
                Log::info("Detected normal (playing) playback for mix {$mix->id}, broadcasting play event");

                if ($this->hasSignificantChanges($previousData, $playbackData)) {
                    event(new PlaybackDataUpdatedEvent($mix, $playbackData));
                } else {
                    Log::debug("Play state unchanged for mix {$mix->id}, skipping broadcast");
                }
                break;
        }

        // Add a default return
        return null;
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
