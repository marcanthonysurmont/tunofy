<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use App\Models\Mix;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResumeMixPlaybackController extends Controller
{
    public function __invoke(Mix $mix, SpotifyService $spotifyService): JsonResponse
    {
        $this->authorize('update', $mix);

        // Check if already playing to avoid unnecessary API calls
        $isPaused = Cache::has("mix:{$mix->id}:paused");

        // Only call the Spotify API if currently paused
        if ($isPaused) {
            $resumeResult = $spotifyService->resumePlayback(Auth::user());
            if (!$resumeResult) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to resume playback'
                ], 500);
            }
        }

        // Remove paused flag
        Cache::forget("mix:{$mix->id}:paused");

        // Get current playback data from cache
        $cacheKey = "mix:playback:" . $mix->id;
        $playbackData = Cache::get($cacheKey, []);

        // Set minimum required fields for a resume event
        $playbackData['is_playing'] = true;
        $playbackData['_timestamp'] = now()->timestamp;

        // Broadcast the resume event
        Log::info("Broadcasting resume event for mix {$mix->id}");
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        // Update cache values
        Cache::put($cacheKey, $playbackData);
        Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));

        Log::info("Playback resumed for mix {$mix->id}");

        return response()->json([
            'success' => true,
            'is_playing' => true
        ]);
    }
}
