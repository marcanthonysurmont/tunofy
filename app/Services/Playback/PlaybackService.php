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
use App\Events\MixStatusChangedEvent;
use App\Events\StatUpdatedEvent;
use App\Models\MixStat;
use App\Services\Playback\SongPlaybackService;
use Illuminate\Support\Facades\DB;
use App\Events\QueueStateUpdatedEvent;

class PlaybackService
{
    public function __construct(
        protected SpotifyService $spotifyService,
        protected PlaybackStateManager $playbackStateManager
    ) 
    {}

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

    /**
     * Play the next song in the mix
     */
    public function playNextSong(Mix $mix): array
    {
        // Get the user to use for playback
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        // Get current playback data for statistics
        $playbackData = $this->spotifyService->getCurrentPlayback($user);

        // Get the next song and handle current song
        $songPlaybackService = app(SongPlaybackService::class);
        $currentSong = QueueSong::currentlyPlayingForMix($mix);

        // Track song history for "previous song" navigation
        if ($currentSong) {
            $this->playbackStateManager->addToSongHistory($mix, $currentSong->id);
            $currentSong->update(['status' => 'finished', 'played_at' => now()]);
        }

        // Check and extend queue if running low
        $pendingSongsCount = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingSongsCount <= 5) {
            Log::info("Queue running low during skip, extending queue for mix {$mix->id}");
            $songPlaybackService->extendQueueIfNeeded($mix);
        }

        // Update stats
        MixStat::updateOrCreate(
            ['mix_id' => $mix->id],
            [
                'songs_played' => DB::raw('songs_played + 1'),
                'minutes_played' => DB::raw('minutes_played + ' . ($playbackData['progress_ms'] ?? 0) / 60000),
            ]
        );
        StatUpdatedEvent::dispatch($mix);

        // Get the next song
        $nextSong = $songPlaybackService->getNextSongToPlay($mix->id);

        // Handle queue completion
        if (!$nextSong) {
            $this->spotifyService->pausePlayback($user);
            $mix->update(['is_active' => false]);
            event(new MixStatusChangedEvent($mix, false, 'queue_completed'));
            Cache::put("mix:{$mix->id}:queue_completed", true, now()->addHours(1));

            return [
                'success' => false,
                'queue_completed' => true,
                'message' => 'Queue completed'
            ];
        }

        // Prepare playback data
        $playbackData = [
            'is_playing' => true,
            'progress_ms' => 0,
            'item' => [
                'id' => $nextSong->song->spotify_id,
                'name' => $nextSong->song->name,
                'duration_ms' => $nextSong->song->duration_ms,
                'artists' => [['name' => $nextSong->song->artist]],
                'album' => [
                    'images' => [['url' => $nextSong->song->image_url]]
                ]
            ],
            '_timestamp' => now()->timestamp,
            '_action' => 'next'
        ];

        // Play on Spotify
        $deviceId = $this->playbackStateManager->getDeviceId($mix);
        $success = $this->playTrackWithOptimizedActivation($user, $nextSong->song->spotify_id, $mix, $deviceId);

        if ($success) {
            // Update status and state
            $nextSong->update(['status' => 'playing', 'played_at' => now()]);
            $this->playbackStateManager->setManualChange($mix);
            $this->playbackStateManager->setPlaybackData($mix, $playbackData);

            // Broadcast updates
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));
            QueueStateUpdatedEvent::dispatch($mix);

            // Cache the response for throttling
            $response = [
                'success' => true,
                'song' => $nextSong->song
            ];
            Cache::put("mix:{$mix->id}:last_next_response", $response, now()->addMinutes(1));

            return $response;
        } else {
            return ['success' => false];
        }
    }

    /**
     * Helper to play a track with optimized device activation
     */
    private function playTrackWithOptimizedActivation(User $user, string $trackId, Mix $mix, ?string $deviceId): bool
    {
        if (!$deviceId) {
            return false;
        }

        // Skip device activation if recently activated
        $recentlyActivated = $this->playbackStateManager->isDeviceRecentlyActivated($mix);
        $activationSuccess = $recentlyActivated;

        if (!$recentlyActivated) {
            $activationSuccess = $this->spotifyService->activateDevice($user, $deviceId);
            if ($activationSuccess) {
                $this->playbackStateManager->setDeviceActivated($mix, true, 30);
            }
        }

        if (!$activationSuccess) {
            return false;
        }

        return $this->spotifyService->playTrackOnDevice($user, $trackId, $deviceId);
    }

    /**
     * Get last cached response for a throttled command
     */
    public function getLastCachedResponse(Mix $mix, string $commandType): ?array
    {
        $lastResponseKey = "mix:{$mix->id}:last_{$commandType}_response";
        $lastResponse = Cache::get($lastResponseKey);

        if ($lastResponse) {
            return array_merge(
                $lastResponse,
                ['throttled' => true]
            );
        }

        return null;
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
