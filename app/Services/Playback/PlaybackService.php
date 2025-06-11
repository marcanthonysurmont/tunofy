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
use App\Events\StatUpdatedEvent;
use App\Models\MixStat;
use Illuminate\Support\Facades\DB;
use App\Events\QueueStateUpdatedEvent;

class PlaybackService
{
    public function __construct(
        protected SpotifyService $spotifyService,
        protected PlaybackStateManager $playbackStateManager,
        protected SongPlaybackService $songPlaybackService
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

        // Ensure we have the minimum required fields
        if (!isset($playbackData['item'])) {
            // Get current playing song to populate missing data
            $currentSong = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'playing')
                ->with('song')
                ->first();

            if ($currentSong) {
                $playbackData['item'] = [
                    'id' => $currentSong->song->spotify_id,
                    'name' => $currentSong->song->name,
                    'duration_ms' => $currentSong->song->duration_ms,
                    'artists' => [['name' => $currentSong->song->artist]],
                    'album' => [
                        'images' => [['url' => $currentSong->song->image_url]]
                    ]
                ];
            }
        }

        // Set playing state and timestamp
        $playbackData['is_playing'] = true;
        $playbackData['_timestamp'] = now()->timestamp;
        $playbackData['_action'] = 'resume';

        // Get the user for playback
        $user = $this->getControllingUser($mix);

        // Check if we're in a takeback situation
        $resumingFromTakeback = !$mix->co_dj_id && $this->playbackStateManager->has($mix, 'recent_owner_takeback');

        // Get the specific song we should play
        $specificTrackId = null;
        if ($resumingFromTakeback) {
            $specificTrackId = $this->playbackStateManager->get($mix, 'switch_track_id');
            Log::info("Owner takeback - looking for specific track: " . ($specificTrackId ?? 'none'));
        }

        // Get the current playing song from the queue
        $currentSong = null;

        // First try to find the exact song that was playing during handoff
        if ($specificTrackId) {
            $currentSong = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'pending')
                ->whereHas('song', function ($query) use ($specificTrackId) {
                    $query->where('spotify_id', $specificTrackId);
                })
                ->with('song')
                ->first();

            if ($currentSong) {
                Log::info("Found the specific pre-switch song (ID: {$currentSong->id}) to resume");
                $currentSong->update(['status' => 'playing']);
            } else {
                // ADDED: Important fallback - if we can't find the specific track as pending
                // Try to find it with ANY status, since statuses might have changed
                Log::info("Couldn't find specific track {$specificTrackId} as pending, trying any status");
                $currentSong = QueueSong::where('mix_id', $mix->id)
                    ->whereHas('song', function ($query) use ($specificTrackId) {
                        $query->where('spotify_id', $specificTrackId);
                    })
                    ->with('song')
                    ->first();

                if ($currentSong) {
                    Log::info("Found the specific pre-switch song with alternate status (ID: {$currentSong->id}) to resume");
                    // Reset all playing songs to pending first
                    QueueSong::where('mix_id', $mix->id)
                        ->where('status', 'playing')
                        ->update(['status' => 'pending']);
                    // Set our found song to playing
                    $currentSong->update(['status' => 'playing']);
                }
            }
        }

        // If no specific song found, get any playing song
        if (!$currentSong) {
            $currentSong = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'playing')
                ->with('song')
                ->first();
        }

        // Return error if no current song found
        if (!$currentSong) {
            return [
                'success' => false,
                'message' => 'No song is currently playing'
            ];
        }

        // Get the position from PlaybackStateManager
        $positionMs = $this->playbackStateManager->getPausedPosition($mix);

        // CRITICAL: Always use position 0 after owner takeback
        if ($resumingFromTakeback) {
            $positionMs = 0;
            Log::info("Owner takeback detected - starting song from beginning");
            $this->playbackStateManager->forget($mix, 'recent_owner_takeback');
        }

        // Log position info
        if ($positionMs > 0) {
            Log::info("Resuming playback at saved position {$positionMs}ms for mix {$mix->id}");
        } else {
            Log::info("Resuming playback from start for mix {$mix->id}");
        }

        // First activate the device if needed (without delay)
        if ($deviceId) {
            $activationSuccess = $this->spotifyService->activateDevice($user, $deviceId);
            if (!$activationSuccess) {
                Log::error("Failed to activate device {$deviceId} for mix {$mix->id}");
                $this->handleDeviceError($mix, $user, $deviceId);
                return ['success' => false, 'message' => 'Failed to activate device'];
            }
        }

        // ALWAYS use direct track play for reliability
        $success = $this->spotifyService->playTrackOnDevice(
            $user,
            $currentSong->song->spotify_id,
            $deviceId,
            $positionMs
        );

        // Handle successful resume
        if ($success) {
            // Remove paused flag
            $this->playbackStateManager->setPaused($mix, false);
            $this->playbackStateManager->setManualChange($mix);

            // CRITICAL: Set a flag to ignore the next track change detection
            if ($resumingFromTakeback) {
                $this->playbackStateManager->set($mix, 'recent_takeback_track_change', true);
                Log::info("Set recent takeback track change flag to prevent auto-advance");

                // Schedule removal of the flag after 5 seconds
                dispatch(function () use ($mix) {
                    $this->playbackStateManager->forget($mix, 'recent_takeback_track_change');
                    Log::info("Cleared recent takeback track change flag");
                })->delay(now()->addSeconds(5));
            }

            // Update cache and broadcast AFTER API success
            $this->playbackStateManager->setPlaybackData($mix, $playbackData);
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));

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
        // Get the current song that's playing
        $currentSong = QueueSong::currentlyPlayingForMix($mix);

        // Find the next song to play
        $nextSong = $this->songPlaybackService->getNextSongToPlay($mix->id);
        if (!$nextSong) {
            return ['success' => false, 'queue_completed' => true];
        }

        // IMPORTANT: Only add current song to history if we have one
        if ($currentSong) {
            // Add to history first, then update status
            $this->playbackStateManager->addToSongHistory($mix, $currentSong->id);
            Log::info("Added song ID {$currentSong->id} to history before playing next song");

            $currentSong->update(['status' => 'finished', 'played_at' => now()]);
        }

        // Update the next song status
        $nextSong->update(['status' => 'playing', 'played_at' => now()]);

        // 2. Prepare the playback data but DON'T broadcast yet
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

        // 3. Spotify API calls with original optimization
        $deviceId = $this->playbackStateManager->getDeviceId($mix);

        // OPTIMIZATION: Skip device activation if it was recently activated
        $recentlyActivated = $this->playbackStateManager->isDeviceRecentlyActivated($mix);
        $activationSuccess = $recentlyActivated;

        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        if (!$recentlyActivated) {
            $activationSuccess = $this->spotifyService->activateDevice($user, $deviceId);
            if ($activationSuccess) {
                $this->playbackStateManager->setDeviceActivated($mix, true, 30);
            }
        }

        $success = false;
        if ($activationSuccess) {
            // OPTIMIZATION: No sleep/delay, immediate play command
            $success = $this->spotifyService->playTrackOnDevice(
                $user,
                $nextSong->song->spotify_id,
                $deviceId
            );
        }

        if ($success) {
            // Update cache BEFORE broadcasting
            $this->playbackStateManager->setManualChange($mix);
            $this->playbackStateManager->setPlaybackData($mix, $playbackData);

            // Broadcast AFTER cache update
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));
            QueueStateUpdatedEvent::dispatch($mix);

            // Prepare response
            $response = [
                'success' => true,
                'song' => [
                    'id' => $nextSong->song->id,
                    'spotify_id' => $nextSong->song->spotify_id,
                    'name' => $nextSong->song->name,
                    'artist' => $nextSong->song->artist,
                    'duration_ms' => $nextSong->song->duration_ms,
                    'image_url' => $nextSong->song->image_url
                ]
            ];

            // Cache response for throttled requests
            $this->cacheCommandResponse($mix, 'next', $response);

            return $response;
        } else {
            // Revert database changes
            $nextSong->update(['status' => 'pending']);
            if ($currentSong) {
                $currentSong->update(['status' => 'playing']);
            }

            Log::error("Failed to play next song on Spotify for mix {$mix->id}");
            return [
                'success' => false,
                'message' => 'Failed to play next song on Spotify'
            ];
        }
    }

    /**
     * Play the previous song in the mix
     */
    public function playPreviousSong(Mix $mix): array
    {
        // Get the current song that's playing
        $currentSong = QueueSong::currentlyPlayingForMix($mix);
        if (!$currentSong) {
            return [
                'success' => false,
                'message' => 'No song is currently playing'
            ];
        }

        // Get song history from PlaybackStateManager
        $previousSongId = $this->playbackStateManager->getPreviousSongFromHistory($mix);

        // Check if we have a previous song
        if (!$previousSongId) {
            Log::info("No previous song found in history for mix {$mix->id}");
            return [
                'success' => false,
                'message' => 'No previous song available'
            ];
        }

        // Get the previous song
        $previousSong = QueueSong::where('mix_id', $mix->id)
            ->where('id', $previousSongId)
            ->with('song')
            ->first();

        if (!$previousSong) {
            return [
                'success' => false,
                'message' => 'Previous song not found'
            ];
        }

        // Update database status BEFORE Spotify API call (fast operations)
        $currentSong->update(['status' => 'pending']);
        $previousSong->update(['status' => 'playing']);

        // Prepare the playback data but DON'T broadcast yet
        $playbackData = [
            'is_playing' => true,
            'item' => [
                'id' => $previousSong->song->spotify_id,
                'name' => $previousSong->song->name,
                'duration_ms' => $previousSong->song->duration_ms,
                'artists' => [['name' => $previousSong->song->artist]],
                'album' => [
                    'images' => [['url' => $previousSong->song->image_url]]
                ]
            ],
            '_timestamp' => now()->timestamp,
            '_action' => 'previous'
        ];

        // Update stats
        $user = $this->getControllingUser($mix);
        $oldPlaybackData = $this->spotifyService->getCurrentPlayback($user);

        MixStat::updateOrCreate(
            ['mix_id' => $mix->id],
            [
                'songs_played' => DB::raw('songs_played + 1'),
                'minutes_played' => DB::raw('minutes_played + ' . ($oldPlaybackData['progress_ms'] ?? 0) / 60000),
            ]
        );
        StatUpdatedEvent::dispatch($mix);

        // OPTIMIZATION: Reuse device activation status from cache
        $deviceId = $this->playbackStateManager->getDeviceId($mix);
        $recentlyActivated = $this->playbackStateManager->isDeviceRecentlyActivated($mix);
        $activationSuccess = $recentlyActivated;

        if (!$recentlyActivated) {
            $activationSuccess = $this->spotifyService->activateDevice($user, $deviceId);
            if ($activationSuccess) {
                $this->playbackStateManager->setDeviceActivated($mix, true, 30);
            }
        }

        $success = false;
        if ($activationSuccess) {
            // Play song immediately
            $success = $this->spotifyService->playTrackOnDevice(
                $user,
                $previousSong->song->spotify_id,
                $deviceId
            );
        }

        if ($success) {
            // Update cache BEFORE broadcasting
            $this->playbackStateManager->setManualChange($mix);
            $this->playbackStateManager->setPlaybackData($mix, $playbackData);

            // Broadcast AFTER cache update
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));
            QueueStateUpdatedEvent::dispatch($mix);

            $response = [
                'success' => true,
                'song' => [
                    'id' => $previousSong->song->id,
                    'spotify_id' => $previousSong->song->spotify_id,
                    'name' => $previousSong->song->name,
                    'artist' => $previousSong->song->artist,
                    'duration_ms' => $previousSong->song->duration_ms,
                    'image_url' => $previousSong->song->image_url
                ]
            ];

            // Cache response for throttled requests
            $this->cacheCommandResponse($mix, 'previous', $response);

            return $response;
        } else {
            // Revert database changes
            $currentSong->update(['status' => 'playing']);
            $previousSong->update(['status' => 'pending']);

            Log::error("Failed to play previous song on Spotify for mix {$mix->id}");
            return [
                'success' => false,
                'message' => 'Failed to play previous song on Spotify'
            ];
        }
    }

    /**
     * Get the last cached response for a command
     */
    public function getLastCachedResponse(Mix $mix, string $commandType): ?array
    {
        $lastResponseKey = "mix:{$mix->id}:last_{$commandType}_response";
        return Cache::get($lastResponseKey);
    }

    /**
     * Cache command response for throttled requests
     */
    public function cacheCommandResponse(Mix $mix, string $commandType, array $response): void
    {
        $lastResponseKey = "mix:{$mix->id}:last_{$commandType}_response";
        Cache::put($lastResponseKey, $response, now()->addMinutes(1));
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
     * Create standardized playback data structure from a song
     */
    private function createPlaybackData(QueueSong $queueSong, string $action, bool $isPlaying = true, int $progressMs = 0): array
    {
        return [
            'is_playing' => $isPlaying,
            'progress_ms' => $progressMs,
            'item' => [
                'id' => $queueSong->song->spotify_id,
                'name' => $queueSong->song->name,
                'duration_ms' => $queueSong->song->duration_ms,
                'artists' => [['name' => $queueSong->song->artist]],
                'album' => [
                    'images' => [['url' => $queueSong->song->image_url]]
                ]
            ],
            '_timestamp' => now()->timestamp,
            '_action' => $action
        ];
    }

    /**
     * Get the controlling user for a mix
     */
    private function getControllingUser(Mix $mix): User
    {
        return $mix->co_dj_id ? $mix->coDj : $mix->user;
    }

    /**
     * Update playback statistics for mix
     */
    private function updatePlaybackStats(Mix $mix, ?array $playbackData = null): void
    {
        try {
            $progressMs = 0;

            if (!$playbackData) {
                $user = $this->getControllingUser($mix);
                $playbackData = $this->spotifyService->getCurrentPlayback($user);
            }

            if ($playbackData && isset($playbackData['progress_ms'])) {
                $progressMs = $playbackData['progress_ms'];
            }

            MixStat::updateOrCreate(
                ['mix_id' => $mix->id],
                [
                    'songs_played' => DB::raw('songs_played + 1'),
                    'minutes_played' => DB::raw('minutes_played + ' . $progressMs / 60000),
                ]
            );

            StatUpdatedEvent::dispatch($mix);
        } catch (Exception $e) {
            Log::error("Error updating playback stats: " . $e->getMessage());
        }
    }

    /**
     * Update state and broadcast event after a successful playback change
     */
    private function updateStateAndBroadcast(Mix $mix, array $playbackData, bool $emitQueueUpdate = true): void
    {
        $this->playbackStateManager->setManualChange($mix);
        $this->playbackStateManager->setPlaybackData($mix, $playbackData);

        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        if ($emitQueueUpdate) {
            QueueStateUpdatedEvent::dispatch($mix);
        }
    }

    /**
     * Prepare standardized song response for API
     */
    private function prepareSongResponse(QueueSong $queueSong, bool $success = true): array
    {
        if (!$success) {
            return ['success' => false];
        }

        return [
            'success' => true,
            'song' => [
                'id' => $queueSong->song->id,
                'spotify_id' => $queueSong->song->spotify_id,
                'name' => $queueSong->song->name,
                'artist' => $queueSong->song->artist,
                'duration_ms' => $queueSong->song->duration_ms,
                'image_url' => $queueSong->song->image_url
            ]
        ];
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
                    Log::info("Found the specific pre-switch song (ID: {$specificSong->id}) to resume after co-DJ removal");
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

    /**
     * Transfer playback to a new device while maintaining current song and position
     */
    public function transferPlayback(Mix $mix, string $deviceId): array
    {
        Log::info("Transferring playback for mix {$mix->id} to device {$deviceId}");

        // Get current song and progress
        $currentQueueSong = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'playing')
            ->with('song')
            ->first();

        if (!$currentQueueSong) {
            return ['error' => 'No song is currently playing', 'code' => 404];
        }

        // Get user and current playback data
        $user = $this->getControllingUser($mix);
        $playbackData = $this->spotifyService->getCurrentPlayback($user);
        $progressMs = $playbackData['progress_ms'] ?? 0;

        // Activate the device first
        $activationSuccess = $this->spotifyService->activateDevice($user, $deviceId);
        if (!$activationSuccess) {
            return ['error' => 'Failed to activate device', 'code' => 500];
        }

        // Short pause to allow device activation to register
        usleep(200000); // 200ms

        // Play current song at current position
        $playSuccess = $this->spotifyService->playTrackOnDevice(
            $user,
            $currentQueueSong->song->spotify_id,
            $deviceId,
            $progressMs
        );

        if (!$playSuccess) {
            return ['error' => 'Failed to play track on new device', 'code' => 500];
        }

        // Update device state
        $this->playbackStateManager->setDeviceId($mix, $deviceId);
        $this->playbackStateManager->setDeviceChanged($mix);

        // Broadcast device update
        event(new DeviceUpdatedEvent($mix));

        // Get updated playback data and broadcast
        $updatedPlaybackData = $this->spotifyService->getCurrentPlayback($user);
        if ($updatedPlaybackData) {
            $this->playbackStateManager->setPlaybackData($mix, $updatedPlaybackData);
            event(new PlaybackDataUpdatedEvent($mix, $updatedPlaybackData));
        }

        return [
            'success' => true,
            'device_id' => $deviceId
        ];
    }

    private function findAppropriateTrackForResumption(Mix $mix, string $specificTrackId = null): ?QueueSong
    {
        if ($specificTrackId) {
            // Try to find the track that was playing when the co-DJ was removed
            $queueSong = QueueSong::where('mix_id', $mix->id)
                ->whereIn('status', ['pending']) // Important: look at pending tracks
                ->whereHas('song', function ($query) use ($specificTrackId) {
                    $query->where('spotify_id', $specificTrackId);
                })
                ->with('song')
                ->first();

            if ($queueSong) {
                Log::info("Found the specific pre-switch song (ID: {$queueSong->id}) to resume after co-DJ removal");
                $queueSong->update(['status' => 'playing']);
                return $queueSong;
            }
        }

        // If no specific track found, get first pending song
        $queueSong = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'pending')
            ->orderBy('order')
            ->with('song')
            ->first();

        if ($queueSong) {
            $queueSong->update(['status' => 'playing']);
            Log::info("Using first pending song {$queueSong->id} after co-DJ removal");
        }

        return $queueSong;
    }
}
