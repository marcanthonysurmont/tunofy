<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Requests\GetSpotifyMixPlaybackRequest;
use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use App\Models\Mix;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class GetSpotifyMixPlaybackController extends Controller
{
    public function __invoke(GetSpotifyMixPlaybackRequest $request, SpotifyService $spotifyService): JsonResponse
    {
        $now = now()->timestamp;
        $requestId = substr(md5($now . rand()), 0, 6); // Generate short request ID

        Log::info("[REQ-{$requestId}] Mix playback request from " . request()->ip() .
            " for mix {$request->input('mix_id')}, max_age={$request->input('max_age', 30)}");

        $mixId = $request->input('mix_id');
        $maxAge = $request->input('max_age', 30);
        $mix = Mix::findOrFail($mixId);

        // Authorization check
        $this->authorize('view', $mix);

        // Status check
        if (!$mix->is_active) {
            return response()->json([
                'status' => 'inactive',
                'is_active' => false
            ]);
        }

        // Log status request
        Log::info("Playback request for mix {$mixId}");

        // Check if we have cached data
        $cacheKey = "spotify:playback:{$mixId}";
        $cachedData = Cache::get($cacheKey);
        $now = now()->timestamp;

        // Check if cached data is fresh enough
        if ($cachedData) {
            $timestamp = $cachedData['_timestamp'] ?? 0;
            $age = $now - $timestamp;

            if ($age <= $maxAge) {
                Log::info("[REQ-{$requestId}] 🔵 CACHE HIT: Serving cached data (age: {$age}s)");
                return response()->json(array_merge($cachedData, ['_fromCache' => true]));
            } else {
                Log::info("[REQ-{$requestId}] 🟠 CACHE STALE: Data is {$age}s old (max: {$maxAge}s)");
            }
        } else {
            Log::info("[REQ-{$requestId}] 🔴 CACHE MISS: No cached data found");
        }

        // Get fresh data from Spotify
        try {
            $user = User::find($mix->user_id);
            Log::info("[REQ-{$requestId}] 📡 Making Spotify API request for user {$user->id}");

            $playbackData = $spotifyService->getCurrentPlayback($user);

            if ($playbackData) {
                $fieldCount = count($playbackData);
                Log::info("Received {$fieldCount} fields in playback data");

                // Add timestamp
                $playbackData['_timestamp'] = now()->timestamp;

                // Update cache with consistent expiration
                $cacheKey = "spotify:playback:{$mixId}";
                Cache::put($cacheKey, $playbackData, 300); // Use the longer time

                return response()->json(array_merge($playbackData, ['_fromCache' => false]));
            } else {
                return response()->json(['error' => 'No active playback found'], 404);
            }
        } catch (\Exception $e) {
            Log::error("Error fetching playback data: " . $e->getMessage());
            return response()->json(['error' => 'Could not fetch playback data'], 500);
        }
    }
}
