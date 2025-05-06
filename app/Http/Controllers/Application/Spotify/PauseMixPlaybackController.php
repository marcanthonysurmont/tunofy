<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use App\Models\Mix;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PauseMixPlaybackController extends Controller
{
    public function __invoke(Mix $mix, SpotifyService $spotifyService): JsonResponse
    {
        $this->authorize('update', $mix);

        $pauseResult = $spotifyService->pausePlayback(Auth::user());

        if (!$pauseResult) {
            return response()->json(['error' => 'Failed to pause playback'], 500);
        }

        Cache::put("mix:{$mix->id}:paused", true);

        Log::info("Playback paused for mix {$mix->id} and polling suspended");

        return response()->json([
            'success' => true,
            'is_playing' => false
        ]);
    }
}
