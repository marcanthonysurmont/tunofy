<?php

namespace App\Services;

use App\Models\Mix;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PlaybackStateManager
{
    // State flags that trigger immediate polling
    public const DEVICE_CHANGED = 'device_changed';
    public const MANUAL_CHANGE = 'manual_change';
    public const PLAYBACK_CHANGED = 'playback_changed';

    // Playback state flags
    public const PAUSED = 'paused';
    public const QUEUE_COMPLETED = 'queue_completed';
    public const POLLING_ACTIVE = 'polling_active';

    // Data tracking for polling and progress
    public const LAST_POLL_TIME = 'last_poll_time';
    public const LAST_POLL_DATA = 'last_poll_data';
    public const SONG_NEARING_END = 'song_nearing_end';
    public const SONG_PROGRESS = 'song_progress';
    public const SONG_DURATION = 'song_duration';

    public const PLAYER_STATE_NORMAL = 'normal';
    public const PLAYER_STATE_NO_PLAYBACK = 'no_playback';
    public const PLAYER_STATE_TRACK_MISMATCH = 'track_mismatch';
    public const PLAYER_STATE_FINISHED = 'finished';
    public const PLAYER_STATE_STUCK = 'stuck';
    public const PLAYER_STATE_MANUAL_SEEK_END = 'manual_seek_end';
    public const PLAYER_STATE_PLAYING_TOO_LONG = 'playing_too_long';
    public const PLAYER_STATE_TRACK_ENDED = 'track_ended';

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
        $this->set($mix, self::MANUAL_CHANGE, true);
        Log::info("Set manual change flag for mix {$mix->id}");
    }

    /**
     * Set device changed flag - will be consumed on first poll
     */
    public function setDeviceChanged(Mix $mix): void
    {
        $this->set($mix, self::DEVICE_CHANGED, true);
        Log::info("Set device changed flag for mix {$mix->id}");
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
     * Store current playback data
     */
    public function setPlaybackData(Mix $mix, ?array $playbackData): void
    {
        $this->set($mix, self::LAST_POLL_DATA, $playbackData);
    }

    /**
     * Get current playback data
     */
    public function getPlaybackData(Mix $mix): ?array
    {
        return $this->get($mix, self::LAST_POLL_DATA);
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
}
