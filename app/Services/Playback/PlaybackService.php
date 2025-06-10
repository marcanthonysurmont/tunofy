<?php

namespace App\Services\Playback;

use App\Models\Mix;
use App\Models\User;
use App\Services\Spotify\SpotifyService;
use App\Services\Playback\PlaybackStateManager;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;

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
}
