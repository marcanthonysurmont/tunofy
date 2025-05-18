<?php

namespace App\Services\Playback;

use App\Models\Mix;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\MixStatusChangedEvent;
use App\Models\QueueSong;
use App\Services\Spotify\SpotifyService;

class PlaybackStateManager
{
    // Primary state flags (trigger immediate polling)
    public const DEVICE_CHANGED = 'device_changed';
    public const MANUAL_CHANGE = 'manual_change';
    public const PLAYBACK_CHANGED = 'playback_changed';

    // Playback state indicators
    public const PAUSED = 'paused';
    public const QUEUE_COMPLETED = 'queue_completed';
    public const POLLING_ACTIVE = 'polling_active';

    // Player state identifiers
    public const PLAYER_STATE_NORMAL = 'normal';
    public const PLAYER_STATE_NO_PLAYBACK = 'no_playback';
    public const PLAYER_STATE_TRACK_ENDED = 'track_ended';
    public const PLAYER_STATE_TRACK_MISMATCH = 'track_mismatch';
    public const PLAYER_STATE_STUCK = 'stuck';
    public const PLAYER_STATE_MANUAL_SEEK_END = 'manual_seek_end';
    public const SONG_NEARING_END = 'song_nearing_end';

    // Technical tracking fields
    public const LAST_POLL_TIME = 'last_poll_time';
    public const LAST_POLL_DATA = 'last_poll_data';
    public const SONG_PROGRESS = 'song_progress';
    public const SONG_DURATION = 'song_duration';

    /**
     * Format a cache key for a mix state
     */
    public function formatKey(int $mixId, string $state): string
    {
        return "playback:mix:{$mixId}:{$state}";
    }

    /**
     * Set a playback state value
     */
    public function set(Mix $mix, string $state, $value = true): void
    {
        $key = $this->formatKey($mix->id, $state);

        if ($value === false) {
            Cache::forget($key);
            return;
        }

        Cache::put($key, $value);
    }

    /**
     * Get a playback state value
     */
    public function get(Mix $mix, string $state, $default = null)
    {
        return Cache::get($this->formatKey($mix->id, $state), $default);
    }

    /**
     * Check if a playback state exists
     */
    public function has(Mix $mix, string $state): bool
    {
        return Cache::has($this->formatKey($mix->id, $state));
    }

    /**
     * Remove a playback state
     */
    public function forget(Mix $mix, string $state): void
    {
        Cache::forget($this->formatKey($mix->id, $state));
    }

    /**
     * Convenience method to set a mix as paused
     */
    public function setPaused(Mix $mix, bool $isPaused = true): void
    {
        if ($isPaused) {
            $this->set($mix, self::PAUSED, true);
            Log::info("Set mix {$mix->id} as paused");
        } else {
            $this->forget($mix, self::PAUSED);
            Log::info("Cleared paused state for mix {$mix->id}");
        }
    }

    /**
     * Convenience method to check if a mix is paused
     */
    public function isPaused(Mix $mix): bool
    {
        return $this->has($mix, self::PAUSED);
    }

    /**
     * Set manual change flag - will be consumed on first poll
     */
    public function setManualChange(Mix $mix): void
    {
        $this->set($mix, self::MANUAL_CHANGE);
        $this->set($mix, 'manual_change_timestamp');
        Log::info("Set manual change flag for mix {$mix->id}");
    }

    /**
     * Set device changed flag with an expiration time
     */
    public function setDeviceChanged(Mix $mix, bool $value = true, int $seconds = 5): void
    {
        if ($value) {
            // Use Cache::put directly with expiration like setDeviceChangeGracePeriod does
            Cache::put($this->formatKey($mix->id, 'device_changed'), true, now()->addSeconds($seconds));
            Log::info("Set device change grace period of {$seconds}s for mix {$mix->id}");
        } else {
            $this->forget($mix, 'device_changed');
        }
    }

    /**
     * Check if device was recently changed
     */
    public function hasDeviceChanged(Mix $mix): bool
    {
        return $this->has($mix, 'device_changed');
    }

    /**
     * Clear flags that only need to affect a single poll
     * Called after a poll has processed the flags
     */
    public function consumePollFlags(Mix $mix): void
    {
        $this->forget($mix, self::MANUAL_CHANGE);
        $this->forget($mix, self::DEVICE_CHANGED);
        $this->forget($mix, self::PLAYBACK_CHANGED);
    }

    /**
     * Mark queue as completed
     */
    public function setQueueCompleted(Mix $mix, bool $isCompleted = true): void
    {
        if ($isCompleted) {
            $this->set($mix, self::QUEUE_COMPLETED, true);

            // Update the model (this is now working)
            $mix->update(['is_active' => false]);
            Log::info("Marked mix {$mix->id} as inactive in database after queue completion");

            // Broadcast the event
            event(new MixStatusChangedEvent(
                $mix,
                false,
                'queue_completed'
            ));
            Log::info("Broadcast MixStatusChangedEvent for mix {$mix->id} deactivation");

            // Also pause the playback when setting completion
            try {
                $user = $mix->co_dj_id ? $mix->coDj : $mix->user;
                app(SpotifyService::class)->pausePlayback($user);
                Log::info("Paused playback after queue completion for mix {$mix->id}");
            } catch (\Exception $e) {
                Log::error("Failed to pause playback after queue completion: " . $e->getMessage());
            }

            Log::info("Set queue completed for mix {$mix->id}");
        } else {
            $this->forget($mix, self::QUEUE_COMPLETED);
            Log::info("Cleared queue completed state for mix {$mix->id}");
        }
    }

    /**
     * Check if queue is completed
     */
    public function isQueueCompleted(Mix $mix): bool
    {
        return $this->has($mix, self::QUEUE_COMPLETED);
    }

    /**
     * Store device ID for a mix
     */
    public function setDeviceId(Mix $mix, string $deviceId): void
    {
        $this->set($mix, 'device_id', $deviceId);
        Log::info("Set device ID {$deviceId} for mix {$mix->id}");
    }

    /**
     * Get device ID for a mix
     */
    public function getDeviceId(Mix $mix): ?string
    {
        return $this->get($mix, 'device_id');
    }

    /**
     * Get playback data
     */
    public function getPlaybackData(Mix $mix): ?array
    {
        // Use EXACTLY the same key format as where you're storing it
        $key = "mix:{$mix->id}:playback"; // Remove "_data" to match where you're storing it
        $data = Cache::get($key);

        // Debug
        if ($data) {
            Log::debug("Retrieved playback data for mix {$mix->id} with ID " .
                ($data['item']['id'] ?? 'unknown'));
        } else {
            Log::debug("No playback data found for mix {$mix->id} in cache");
        }

        return $data;
    }

    /**
     * Set playback data
     */
    public function setPlaybackData(Mix $mix, array $data): void
    {
        // Use EXACTLY the same key format as where you're retrieving it
        $key = "mix:{$mix->id}:playback"; // Be explicit about this key
        Cache::put($key, $data);

        // Debug log to see what's stored
        Log::debug("Stored playback data for mix {$mix->id} with ID " .
            ($data['item']['id'] ?? 'unknown'));
    }

    /**
     * Update last poll time
     */
    public function updatePollTime(Mix $mix): void
    {
        $this->set($mix, self::LAST_POLL_TIME, now()->timestamp);
    }

    /**
     * Get last poll time
     */
    public function getLastPollTime(Mix $mix): ?int
    {
        return $this->get($mix, self::LAST_POLL_TIME);
    }

    /**
     * Clear all states for a mix except device ID
     */
    public function clearAllStates(Mix $mix): void
    {
        // Save device ID if exists
        $deviceId = $this->getDeviceId($mix);

        if ($deviceId) {
            Log::info("Preserved device ID {$deviceId} for mix {$mix->id} during cache clearing");
        }

        // Clear all possible states by name
        $this->forget($mix, self::DEVICE_CHANGED);
        $this->forget($mix, self::MANUAL_CHANGE);
        $this->forget($mix, self::PLAYBACK_CHANGED);
        $this->forget($mix, self::PAUSED);
        $this->forget($mix, self::QUEUE_COMPLETED);
        $this->forget($mix, self::POLLING_ACTIVE);
        $this->forget($mix, self::LAST_POLL_TIME);
        $this->forget($mix, self::LAST_POLL_DATA);
        $this->forget($mix, self::SONG_NEARING_END);
        $this->forget($mix, self::SONG_PROGRESS);
        $this->forget($mix, self::SONG_DURATION);

        // Restore device ID if it existed
        if ($deviceId) {
            $this->setDeviceId($mix, $deviceId);
        }

        Log::info("Cleared cache entries for mix {$mix->id} while preserving device selection");
    }

    /**
     * Reset the queue position for a mix
     */
    public function resetQueuePosition(Mix $mix): void
    {
        $this->set($mix, 'queue_position', 0);
        Log::info("Reset queue position for mix {$mix->id}");
    }

    /**
     * Update tracking for song progress
     */
    public function setSongProgress(Mix $mix, int $progressMs, int $durationMs): void
    {
        $this->set($mix, self::SONG_PROGRESS, $progressMs);
        $this->set($mix, self::SONG_DURATION, $durationMs);

        // Calculate remaining percentage
        $remainingMs = $durationMs - $progressMs;
        $percentRemaining = ($remainingMs / $durationMs) * 100;

        // Flag if song is nearing end (less than 15% remaining)
        if ($percentRemaining <= 15) {
            $this->set($mix, self::SONG_NEARING_END, true);
        } else {
            $this->forget($mix, self::SONG_NEARING_END);
        }
    }

    /**
     * Store current song information
     */
    public function setCurrentSong(Mix $mix, ?QueueSong $queueSong): void
    {
        if ($queueSong) {
            $this->set($mix, 'current_song_id', $queueSong->id);
        } else {
            $this->forget($mix, 'current_song_id');
        }
    }

    /**
     * Get current song ID
     */
    public function getCurrentSongId(Mix $mix): ?int
    {
        return $this->get($mix, 'current_song_id');
    }

    /**
     * Get current song details
     */
    public function getCurrentSong(Mix $mix): ?array
    {
        $songId = $this->getCurrentSongId($mix);
        if (!$songId) {
            return null;
        }

        $queueSong = QueueSong::with('song')->find($songId);
        if (!$queueSong) {
            return null;
        }

        return [
            'id' => $queueSong->id,
            'spotify_id' => $queueSong->song->spotify_id,
            'name' => $queueSong->song->name,
            'artists' => $queueSong->song->artists,
            'album' => $queueSong->song->album
        ];
    }

    public function getPlaybackState(Mix $mix): array
    {
        return [
            'is_paused' => $this->isPaused($mix),
            'is_queue_completed' => $this->isQueueCompleted($mix),
            'device_id' => $this->getDeviceId($mix),
            'current_song' => $this->getCurrentSong($mix),
            'last_poll_time' => $this->getLastPollTime($mix),
            'song_progress' => $this->get($mix, self::SONG_PROGRESS),
            'song_duration' => $this->get($mix, self::SONG_DURATION),
            'song_nearing_end' => $this->has($mix, self::SONG_NEARING_END),
            'pending_flags' => [
                'device_changed' => $this->has($mix, self::DEVICE_CHANGED),
                'manual_change' => $this->has($mix, self::MANUAL_CHANGE),
                'playback_changed' => $this->has($mix, self::PLAYBACK_CHANGED),
            ]
        ];
    }

    /**
     * Should we poll right now?
     * This considers both timing and priority flags
     */
    public function shouldPoll(Mix $mix): bool
    {
        // If any high-priority flags are set, poll immediately
        if ($this->has($mix, self::MANUAL_CHANGE) ||
            $this->has($mix, self::DEVICE_CHANGED) ||
            $this->has($mix, self::PLAYBACK_CHANGED)) {
            return true;
        }

        // If no last poll time, should poll
        $lastPollTime = $this->getLastPollTime($mix);
        if (!$lastPollTime) {
            return true;
        }

        // Poll at most every 3 seconds unless flags are set
        return now()->timestamp - $lastPollTime > 3;
    }

    /**
     * Mark polling as active to prevent duplicate polls
     * Returns true if successfully marked, false if already polling
     */
    public function startPolling(Mix $mix): bool
    {
        if ($this->has($mix, self::POLLING_ACTIVE)) {
            return false;
        }

        $this->set($mix, self::POLLING_ACTIVE, true);
        return true;
    }

    /**
     * Mark polling as complete
     */
    public function endPolling(Mix $mix): void
    {
        $this->forget($mix, self::POLLING_ACTIVE);
    }

    /**
     * Calculate seconds until next poll
     */
    public function getSecondsUntilNextPoll(Mix $mix): int
    {
        // If priority flags set, poll immediately
        if ($this->has($mix, self::MANUAL_CHANGE) ||
            $this->has($mix, self::DEVICE_CHANGED) ||
            $this->has($mix, self::PLAYBACK_CHANGED)) {
            return 0;
        }

        $lastPollTime = $this->getLastPollTime($mix);
        if (!$lastPollTime) {
            return 0;
        }

        $secondsSinceLastPoll = now()->timestamp - $lastPollTime;
        $secondsUntilNextPoll = max(0, 3 - $secondsSinceLastPoll);

        return $secondsUntilNextPoll;
    }

    /**
     * Check if we're in a device change grace period
     */
    public function isInDeviceChangeGracePeriod(Mix $mix): bool
    {
        return Cache::has("mix:{$mix->id}:device_changed");
    }

    /**
     * Set user paused flag
     */
    public function setUserPaused(Mix $mix, bool $isPaused = true): void
    {
        if ($isPaused) {
            Cache::put("mix:{$mix->id}:user_paused", true, now()->addMinutes(30));
            Log::info("Set user paused flag for mix {$mix->id}");
        } else {
            Cache::forget("mix:{$mix->id}:user_paused");
            Log::info("Cleared user paused flag for mix {$mix->id}");
        }
    }

    /**
     * Track for user switch
     */
    public function setUserSwitchSongId(Mix $mix, int $songId): void
    {
        $this->set($mix, 'user_switch_song_id', $songId, 60);
        Log::info("Set user switch song ID {$songId} for mix {$mix->id}");
    }

    /**
     * Track queue statistics
     */
    public function setPendingSongCount(Mix $mix, int $count): void
    {
        Cache::put("mix:{$mix->id}:pending_count", $count, now()->addMinutes(1));
    }

    /**
     * Get pending song count
     */
    public function getPendingSongCount(Mix $mix): ?int
    {
        return Cache::get("mix:{$mix->id}:pending_count");
    }

    /**
     * Get song history for a mix
     */
    public function getSongHistory(Mix $mix): array
    {
        return Cache::get("mix:{$mix->id}:song_history", []);
    }

    /**
     * Update song history by adding a song to history
     */
    public function addToSongHistory(Mix $mix, int $songId): void
    {
        $historyKey = "mix:{$mix->id}:song_history";
        $songHistory = $this->getSongHistory($mix);

        // Add song to history (limit to last 10 songs)
        array_push($songHistory, $songId);
        if (count($songHistory) > 10) {
            array_shift($songHistory);
        }

        Cache::put($historyKey, $songHistory, now()->addHours(1));
        Log::info("Added song {$songId} to history for mix {$mix->id}");
    }

    /**
     * Get a song from history and remove it
     */
    public function getPreviousSongFromHistory(Mix $mix): ?int
    {
        $historyKey = "mix:{$mix->id}:song_history";
        $songHistory = $this->getSongHistory($mix);

        if (empty($songHistory)) {
            return null;
        }

        // Get the last played song ID from history
        $previousSongId = array_pop($songHistory);

        // Store updated history back in cache
        Cache::put($historyKey, $songHistory, now()->addHours(1));

        return $previousSongId;
    }

    /**
     * Store paused position for a mix
     */
    public function setPausedPosition(Mix $mix, int $positionMs): void
    {
        Cache::put("mix:{$mix->id}:paused_position", $positionMs, now()->addHours(1));
        Log::info("Saved position {$positionMs}ms before pausing mix {$mix->id}");
    }

    /**
     * Get paused position for a mix
     */
    public function getPausedPosition(Mix $mix, int $default = 0): int
    {
        return Cache::get("mix:{$mix->id}:paused_position", $default);
    }

    /**
     * Mark device as recently activated to prevent duplicate activations
     */
    public function setDeviceActivated(Mix $mix, bool $activated = true, int $seconds = 30): void
    {
        if ($activated) {
            Cache::put("mix:{$mix->id}:device_activated", true, now()->addSeconds($seconds));
            Log::info("Marked device as activated for mix {$mix->id} for {$seconds} seconds");
        } else {
            Cache::forget("mix:{$mix->id}:device_activated");
        }
    }

    /**
     * Check if device was recently activated
     */
    public function isDeviceRecentlyActivated(Mix $mix): bool
    {
        return Cache::has("mix:{$mix->id}:device_activated");
    }

    /**
     * Check if a command is being throttled
     */
    public function isThrottled(Mix $mix, string $type, float $throttleSeconds = 1.0): bool
    {
        $key = "mix:{$mix->id}:last_{$type}";
        $lastTime = $this->get($mix, "last_{$type}", 0);
        $now = microtime(true);

        // Update the timestamp
        $this->set($mix, "last_{$type}", $now);

        // Check if we're throttled
        return ($now - $lastTime) < $throttleSeconds;
    }

    /**
     * Set co-DJ recent takeback flag
     */
    public function setRecentOwnerTakeback(Mix $mix, bool $value = true): void
    {
        if ($value) {
            $this->set($mix, 'recent_owner_takeback', true);
            Log::info("Set recent owner takeback flag for mix {$mix->id}");
        } else {
            $this->forget($mix, 'recent_owner_takeback');
        }
    }

    /**
     * Clear track end notification
     */
    public function clearTrackEndNotification(Mix $mix, string $trackId): void
    {
        Cache::forget("mix:{$mix->id}:track_end_notified:{$trackId}");
    }

    /**
     * Check for track end notification
     */
    public function hasTrackEndNotification(Mix $mix, string $trackId): bool
    {
        return $this->has($mix, "track_end_notified:{$trackId}");
    }

    /**
     * Set track end notification
     */
    public function setTrackEndNotification(Mix $mix, string $trackId, int $seconds = 20): void
    {
        $this->set($mix, "track_end_notified:{$trackId}", true);

        // Schedule removal of the flag after specified seconds
        dispatch(function () use ($mix, $trackId) {
            $this->forget($mix, "track_end_notified:{$trackId}");
        })->delay(now()->addSeconds($seconds));
    }

    /**
     * Set seek detected
     */
    public function setSeekDetected(Mix $mix, int $queueSongId, bool $value = true): void
    {
        if ($value) {
            Cache::put("mix:{$mix->id}:seek:{$queueSongId}", true, now()->addSeconds(15));
        } else {
            Cache::forget("mix:{$mix->id}:seek:{$queueSongId}");
        }
    }

    /**
     * Set recent track change flag
     */
    public function setRecentTrackChangeFlag(Mix $mix, bool $value = true, int $seconds = 5): void
    {
        if ($value) {
            $this->set($mix, 'recent_takeback_track_change', true);

            // Schedule removal of the flag after specified seconds
            dispatch(function () use ($mix) {
                $this->forget($mix, 'recent_takeback_track_change');
                Log::info("Cleared recent takeback track change flag");
            })->delay(now()->addSeconds($seconds));
        } else {
            $this->forget($mix, 'recent_takeback_track_change');
        }
    }

    /**
     * Check for device failure
     */
    public function hasDeviceFailure(Mix $mix): bool
    {
        return $this->has($mix, 'device_failure');
    }

    public function hasRecentTrackChange(Mix $mix): bool
    {
        return $this->has($mix, 'recent_takeback_track_change');
    }
}
