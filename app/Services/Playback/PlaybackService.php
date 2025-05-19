<?php

namespace App\Services\Playback;

use App\Models\Mix;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\Spotify\SpotifyService;

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
    public function getPlaybackData(Mix $mix, string $requestId = null): array
    {
        // Generate request ID if not provided
        $requestId = $requestId ?? substr(md5(now()->timestamp . rand()), 0, 6);

        // Get data through PlaybackStateManager
        $cachedData = $this->playbackStateManager->getPlaybackData($mix);

        // If we have cached data, use it
        if ($cachedData) {
            Log::info("[REQ-{$requestId}] Using cached data");
            if (!is_array($cachedData)) {
                Log::warning("[REQ-{$requestId}] Cached data is not an array, converting");
                $cachedData = (array)$cachedData;
            }
            return array_merge($cachedData, ['_fromCache' => true]);
        }

        // No cached data, need to fetch fresh data
        try {
            $user = User::find($mix->user_id);
            $freshData = $this->spotifyService->getCurrentPlayback($user);

            if ($freshData) {
                Log::info("[REQ-{$requestId}] 🔴 First fetch or no cached data, using fresh data");
                if (!is_array($freshData)) {
                    Log::warning("[REQ-{$requestId}] Fresh data is not an array, converting");
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
        } catch (\Exception $e) {
            // Error and no cached data
            Log::error("[REQ-{$requestId}] Error: " . $e->getMessage());
            return ['error' => 'Could not fetch playback data: ' . $e->getMessage()];
        }
    }
}
