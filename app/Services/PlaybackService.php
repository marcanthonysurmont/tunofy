<?php

namespace App\Services;

use App\Models\Mix;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PlaybackService
{
    protected $spotifyService;

    public function __construct(SpotifyService $spotifyService)
    {
        $this->spotifyService = $spotifyService;
    }

    /**
     * Get playback data for a mix, optimized for server-side polling architecture
     */
    public function getPlaybackData(Mix $mix, string $requestId = null): array
    {
        // Generate request ID if not provided
        $requestId = $requestId ?? substr(md5(now()->timestamp . rand()), 0, 6);

        $cacheKey = "spotify:playback:{$mix->id}";
        $cachedData = Cache::get($cacheKey);

        // If we have cached data, use it
        if ($cachedData) {
            Log::info("[REQ-{$requestId}] Using cached data");
            return array_merge($cachedData, ['_fromCache' => true]);
        }

        // No cached data, need to fetch fresh data
        try {
            $user = User::find($mix->user_id);
            $freshData = $this->spotifyService->getCurrentPlayback($user);

            if ($freshData) {
                Log::info("[REQ-{$requestId}] 🔴 First fetch or no cached data, using fresh data");
                $freshData['_timestamp'] = now()->timestamp;
                Cache::put($cacheKey, $freshData);
                
                return array_merge($freshData, ['_fromCache' => false]);
            }

            // No fresh data and no cached data
            return [
                'status' => 'no_active_playback',
                '_timestamp' => now()->timestamp,
                '_fromCache' => false
            ];

        } catch (\Exception $e) {
            // Error and no cached data
            Log::error("[REQ-{$requestId}] Error: " . $e->getMessage());
            return ['error' => 'Could not fetch playback data: ' . $e->getMessage()];
        }
    }
}
