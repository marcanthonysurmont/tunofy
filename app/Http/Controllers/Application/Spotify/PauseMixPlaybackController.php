<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class PauseMixPlaybackController extends Controller
{
    public function __invoke(Mix $mix, PlaybackService $playbackService): JsonResponse
    {
        $this->authorize('controlPlayback', $mix);

        // First check for throttling conditions
        if ($playbackService->shouldThrottleCommand($mix)) {
            Log::info("Throttling pause command for mix {$mix->id}");
            return response()->json([
                'success' => true,
                'is_playing' => false,
                'action' => 'pause',
                'throttled' => true
            ]);
        }

        try {
            // PAUSE FLOW:
            // 1. Save current playback position before pausing
            // 2. Send pause command to Spotify API
            // 3. Update playback state in cache
            // 4. Broadcast updated state to clients
            $result = $playbackService->pauseMixPlayback($mix);

            return response()->json($result);

        } catch (Exception $e) {
            Log::error("Error pausing playback: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause playback: ' . $e->getMessage()
            ]);
        }
    }
}
