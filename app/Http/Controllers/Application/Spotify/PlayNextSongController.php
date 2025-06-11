<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class PlayNextSongController extends Controller
{
    public function __invoke(Mix $mix, PlaybackService $playbackService): JsonResponse
    {
        $this->authorize('controlPlayback', $mix);

        // Check for throttling
        if ($playbackService->shouldThrottleCommand($mix)) {
            Log::info("Throttling next song command for mix {$mix->id}");

            // Return cached response if available
            $cachedResponse = $playbackService->getLastCachedResponse($mix, 'next');
            if ($cachedResponse) {
                return response()->json($cachedResponse);
            }

            return response()->json([
                'success' => true,
                'throttled' => true,
                'message' => 'Command throttled'
            ]);
        }

        try {
            // FLOW:
            // 1. Process current song and mark as finished
            // 2. Check and extend queue if needed
            // 3. Get next song or handle queue completion
            // 4. Play on Spotify and update state
            // 5. Broadcast updates and return response
            $result = $playbackService->playNextSong($mix);

            // Cache the response explicitly here too for redundancy
            $playbackService->cacheCommandResponse($mix, 'next', $result);

            return response()->json($result);
        } catch (Exception $e) {
            Log::error("Error playing next song: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to play next song: ' . $e->getMessage()
            ], 500);
        }
    }
}
