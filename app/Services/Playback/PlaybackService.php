<?php

namespace App\Services\Playback;

use App\Models\Mix;
use App\Models\User;
use App\Services\Spotify\SpotifyService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\DeviceUpdatedEvent;
use App\Models\QueueSong;
use App\Services\Playback\PlaybackStateManager;

class PlaybackService
{
    public function __construct(
        protected SpotifyService $spotifyService,
        protected PlaybackStateManager $playbackStateManager
    ) {
    }

    /**
     * Get playback data for a mix, optimized for server-side polling architecture
     */
    public function getPlaybackData(Mix $mix): array
    {
        // Get data through PlaybackStateManager
        $cachedData = $this->playbackStateManager->getPlaybackData($mix);

        // If we have cached data, use it
        if ($cachedData) {
            if (!is_array($cachedData)) {
                $cachedData = (array)$cachedData;
            }
            return array_merge($cachedData, ['_fromCache' => true]);
        }

        // No cached data, need to fetch fresh data
        try {
            $user = User::find($mix->user_id);
            $freshData = $this->spotifyService->getCurrentPlayback($user);

            if ($freshData) {
                if (!is_array($freshData)) {
                    if (is_string($freshData) && json_validate($freshData)) {
                        $freshData = json_decode($freshData, true);
                    } else {
                        $freshData = (array)$freshData;
                    }
                }
                $freshData['_timestamp'] = now()->timestamp;

                // Store in PlaybackStateManager
                $this->playbackStateManager->setPlaybackData($mix, $freshData);

                return array_merge($freshData, ['_fromCache' => false]);
            }

            // No fresh data and no cached data
            $noPlaybackData = [
                'status' => 'no_active_playback',
                '_timestamp' => now()->timestamp
            ];

            // Store in PlaybackStateManager
            $this->playbackStateManager->setPlaybackData($mix, $noPlaybackData);

            return array_merge($noPlaybackData, ['_fromCache' => false]);
        } catch (Exception $e) {
            // Error and no cached data
            return ['error' => 'Could not fetch playback data: ' . $e->getMessage()];
        }
    }


    /**
     * Check if commands should be throttled
     */
    public function shouldThrottleCommand(Mix $mix): bool
    {
        $lastCommandKey = "mix:{$mix->id}:last_command";
        $lastCommandTime = Cache::get($lastCommandKey, 0);
        $now = microtime(true);

        // Throttle if less than 500ms has passed
        $shouldThrottle = ($now - $lastCommandTime < 0.5);

        // Always update the timestamp
        Cache::put($lastCommandKey, $now, now()->addMinutes(5));

        return $shouldThrottle;
    }

    /**
     * Pause playback for a mix
     */
    public function pauseMixPlayback(Mix $mix): array
    {
        // Get current playback data
        $playbackData = $this->playbackStateManager->getPlaybackData($mix) ?? [];

        // Update playback data with pause state
        $playbackData['is_playing'] = false;
        $playbackData['_timestamp'] = now()->timestamp;
        $playbackData['_action'] = 'pause';

        // Store current playback position before pausing
        $position = $this->captureCurrentPosition($mix);
        if ($position !== null) {
            $playbackData['progress_ms'] = $position;
        }

        // Get the appropriate user and device
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;
        $deviceId = $this->playbackStateManager->getDeviceId($mix);

        // Pause playback via Spotify API
        $success = $this->spotifyService->pausePlayback($user, $deviceId);

        if ($success) {
            // Update state and broadcast
            $this->playbackStateManager->setPaused($mix, true);
            $this->playbackStateManager->setManualChange($mix);
            $this->playbackStateManager->setUserPaused($mix, true);
            $this->playbackStateManager->setPlaybackData($mix, $playbackData);

            // Broadcast event after successful API call
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));

            return [
                'success' => true,
                'is_playing' => false,
                'action' => 'pause'
            ];
        } else {
            Log::error("Failed to pause playback on Spotify for mix {$mix->id}");
            return [
                'success' => false,
                'message' => 'Failed to pause playback on Spotify'
            ];
        }
    }

    /**
     * Capture current playback position
     */
    private function captureCurrentPosition(Mix $mix): ?int
    {
        try {
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;
            $currentPlaybackData = $this->spotifyService->getCurrentPlayback($user);

            if ($currentPlaybackData && isset($currentPlaybackData['progress_ms'])) {
                $position = $currentPlaybackData['progress_ms'];
                $this->playbackStateManager->setPausedPosition($mix, $position);
                Log::info("Saving position {$position}ms before pausing mix {$mix->id}");
                return $position;
            }
        } catch (Exception $e) {
            Log::error("Error fetching current playback position: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Resume playback for a mix
     */
    public function resumeMixPlayback(Mix $mix, ?string $deviceId = null): array
    {
        // Set device ID if provided
        if ($deviceId) {
            $this->playbackStateManager->setDeviceId($mix, $deviceId);
            Log::info("Using device ID {$deviceId} to resume playback for mix {$mix->id}");
        } else {
            // Get stored device ID if none provided
            $deviceId = $this->playbackStateManager->getDeviceId($mix);
        }

        // Get playback data through PlaybackStateManager
        $playbackData = $this->playbackStateManager->getPlaybackData($mix) ?? [];

        // Ensure we have the minimum required fields by finding current song
        if (!isset($playbackData['item'])) {
            $currentSong = $this->getCurrentSongData($mix);
            if ($currentSong) {
                $playbackData['item'] = $currentSong;
            }
        }

        // Set playing state and timestamp
        $playbackData['is_playing'] = true;
        $playbackData['_timestamp'] = now()->timestamp;
        $playbackData['_action'] = 'resume';

        // Handle takeover scenarios
        $resumingFromTakeback = !$mix->co_dj_id && $this->playbackStateManager->has($mix, 'recent_owner_takeback');
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;
        $currentSong = $this->findCurrentSongToResume($mix, $resumingFromTakeback);
        $positionMs = $this->determineResumePosition($mix, $resumingFromTakeback);

        // Resume playback
        $success = false;
        if ($currentSong) {
            // Use direct track play for reliability
            $success = $this->spotifyService->playTrackOnDevice(
                $user,
                $currentSong->song->spotify_id,
                $deviceId,
                $positionMs
            );
        } else {
            // Generic resume if no specific track
            $success = $this->spotifyService->resumePlayback($user, $deviceId);
        }

        // Handle device errors
        if (!$success && $deviceId) {
            $this->handleDeviceError($mix, $user, $deviceId);
        }

        // Handle successful resume
        if ($success) {
            $this->handleSuccessfulResume($mix, $playbackData, $resumingFromTakeback);
            return [
                'success' => true,
                'is_playing' => true
            ];
        } else {
            Log::error("Failed to resume playback on Spotify for mix {$mix->id}");
            return [
                'success' => false,
                'message' => 'Failed to resume playback on Spotify'
            ];
        }
    }

    // Helper methods
    private function getCurrentSongData(Mix $mix): ?array
    {
        $currentSong = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'playing')
            ->with('song')
            ->first();

        if ($currentSong) {
            return [
                'id' => $currentSong->song->spotify_id,
                'name' => $currentSong->song->name,
                'duration_ms' => $currentSong->song->duration_ms,
                'artists' => [['name' => $currentSong->song->artist]],
                'album' => [
                    'images' => [['url' => $currentSong->song->image_url]]
                ]
            ];
        }

        return null;
    }

    private function findCurrentSongToResume(Mix $mix, bool $resumingFromTakeback): ?QueueSong
    {
        // Find specific song after takeback if needed
        if ($resumingFromTakeback) {
            $specificTrackId = $this->playbackStateManager->get($mix, 'switch_track_id');
            if ($specificTrackId) {
                $specificSong = QueueSong::where('mix_id', $mix->id)
                    ->where('status', 'pending')
                    ->whereHas('song', function ($query) use ($specificTrackId) {
                        $query->where('spotify_id', $specificTrackId);
                    })
                    ->with('song')
                    ->first();

                if ($specificSong) {
                    $specificSong->update(['status' => 'playing']);
                    return $specificSong;
                }
            }
        }

        // Default to current playing song
        return QueueSong::where('mix_id', $mix->id)
            ->where('status', 'playing')
            ->with('song')
            ->first();
    }

    private function determineResumePosition(Mix $mix, bool $resumingFromTakeback): int
    {
        $positionMs = $this->playbackStateManager->getPausedPosition($mix);

        // Reset position after owner takeback
        if ($resumingFromTakeback) {
            $positionMs = 0;
            $this->playbackStateManager->forget($mix, 'recent_owner_takeback');
        }

        return $positionMs;
    }

    private function handleDeviceError(Mix $mix, User $user, string $deviceId): void
    {
        $devices = $this->spotifyService->getUserDevices($user);
        $deviceFound = false;

        foreach ($devices as $device) {
            if ($device['id'] === $deviceId) {
                $deviceFound = true;
                break;
            }
        }

        if (!$deviceFound) {
            event(new DeviceUpdatedEvent($mix));
        }
    }

    private function handleSuccessfulResume(Mix $mix, array $playbackData, bool $resumingFromTakeback): void
    {
        $this->playbackStateManager->setPaused($mix, false);
        $this->playbackStateManager->setManualChange($mix);

        // Handle takeback flag
        if ($resumingFromTakeback) {
            $this->playbackStateManager->set($mix, 'recent_takeback_track_change', true);

            // Clear flag after 5 seconds
            dispatch(function () use ($mix) {
                $this->playbackStateManager->forget($mix, 'recent_takeback_track_change');
            })->delay(now()->addSeconds(5));
        }

        // Update cache and broadcast
        $this->playbackStateManager->setPlaybackData($mix, $playbackData);
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));
    }
}
