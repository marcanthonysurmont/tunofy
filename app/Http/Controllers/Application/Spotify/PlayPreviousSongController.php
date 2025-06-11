<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\Playback\PlaybackStateManager;

class PlayPreviousSongController extends Controller
{
    public function __invoke(
        Mix $mix,
        PlaybackService $playbackService,
        PlaybackStateManager $playbackStateManager
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);

        // Check for throttling
        if ($playbackService->shouldThrottleCommand($mix)) {
            Log::info("Throttling previous song command for mix {$mix->id}");

            // Return cached response if available
            $cachedResponse = $playbackService->getLastCachedResponse($mix, 'previous');
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
            // PREVIOUS SONG FLOW:
            // 1. Check if a previous song exists in history
            // 2. Update database states (current->pending, previous->playing)
            // 3. Play the previous song on Spotify
            // 4. Update playback state and broadcast changes

            // Add DEBUG logging
            $history = $playbackStateManager->getSongHistory($mix);
            Log::debug("Song history content for mix {$mix->id}: " . json_encode($history));

            $result = $playbackService->playPreviousSong($mix);

            return response()->json($result);
        } catch (Exception $e) {
            Log::error("Error playing previous song: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to play previous song: ' . $e->getMessage()
            ], 500);
        }
    }
}
