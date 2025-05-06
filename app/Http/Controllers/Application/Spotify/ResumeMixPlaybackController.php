<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use App\Models\Mix;
use Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ResumeMixPlaybackController extends Controller
{
    public function __invoke(Mix $mix, SpotifyService $spotifyService): JsonResponse
    {
        try {
            $this->authorize('update', $mix);

            Cache::forget("mix:{$mix->id}:paused");

            $resumeResult = $spotifyService->resumePlayback(Auth::user());

            if (!$resumeResult) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to resume playback'
                ], 500);
            }

            Log::info("Playback resumed for mix {$mix->id} and polling resumed");

            return response()->json([
                'success' => true,
                'is_playing' => true
            ]);
        } catch (\Exception $e) {
            Log::error("Resume mix error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred'
            ], 500);
        }
    }
}
