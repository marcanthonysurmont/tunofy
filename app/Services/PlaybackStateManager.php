<?php

namespace App\Services;

use App\Models\Mix;
use Illuminate\Support\Facades\Cache;

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

    // Set a playback state value
    public function set(Mix $mix, string $state, $value = true): void
    {
        $key = $this->formatKey($mix->id, $state);

        if ($value === false) {
            Cache::forget($key);
            return;
        }

        Cache::put($key, $value);
    }

    // Get a playback state value
    public function get(Mix $mix, string $state, $default = null)
    {
        return Cache::get($this->formatKey($mix->id, $state), $default);
    }

    // Check if a playback state exists
    public function has(Mix $mix, string $state): bool
    {
        return Cache::has($this->formatKey($mix->id, $state));
    }

    // Remove a playback state
    public function forget(Mix $mix, string $state): void
    {
        Cache::forget($this->formatKey($mix->id, $state));
    }

    // Determine if we should poll the mix based on state and timing
    public function shouldPoll(Mix $mix): bool
    {
        // Check for high priority reasons to poll
        if ($this->has($mix, self::DEVICE_CHANGED) ||
            $this->has($mix, self::MANUAL_CHANGE) ||
            $this->has($mix, self::PLAYBACK_CHANGED)) {
            return true;
        }

        // Get last poll time
        $lastPollTime = $this->get($mix, self::LAST_POLL_TIME);

        // If we've never polled, definitely poll
        if (!$lastPollTime) {
            return true;
        }

        // Calculate time since last poll
        $secondsSinceLastPoll = now()->diffInSeconds($lastPollTime);

        // Get the song ending timing info
        $songNearingEnd = $this->get($mix, self::SONG_NEARING_END, false);

        // If song is nearing end, poll more frequently (only matters when playing)
        if ($songNearingEnd && !$this->has($mix, self::PAUSED)) {
            return $secondsSinceLastPoll > 2; // Poll every 2 seconds when near end
        }

        // Standard case - keep consistent polling frequency whether paused or playing
        // This ensures we detect external resume/pause actions quickly
        return $secondsSinceLastPoll > 5; // Every 5 seconds for all states
    }

    // Record that polling has occurred
    public function recordPoll(Mix $mix, ?array $pollData = null): void
    {
        $this->set($mix, self::LAST_POLL_TIME, now());

        if ($pollData) {
            $this->set($mix, self::LAST_POLL_DATA, $pollData);
        }

        // Clear state flags that were consumed by this poll
        $this->forget($mix, self::DEVICE_CHANGED);
        $this->forget($mix, self::MANUAL_CHANGE);
        $this->forget($mix, self::PLAYBACK_CHANGED);
    }

    //Update tracking for song progress to determine polling frequency
    public function updateSongProgress(Mix $mix, int $progressMs, int $durationMs): void
    {
        $this->set($mix, self::SONG_PROGRESS, $progressMs);
        $this->set($mix, self::SONG_DURATION, $durationMs);

        // Calculate remaining percentage
        $remainingMs = $durationMs - $progressMs;
        $percentRemaining = ($remainingMs / $durationMs) * 100;

        // Flag if song is nearing end (less than 15% remaining)
        if ($percentRemaining <= 15) {
            $this->set($mix, self::SONG_NEARING_END, true); // No TTL
        } else {
            $this->forget($mix, self::SONG_NEARING_END);
        }
    }

    // Format a cache key for playback states
    private function formatKey(int $mixId, string $state): string
    {
        return "mix:{$mixId}:playback:{$state}";
    }
}
