<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ResumeMixPlaybackController extends Controller
{
    public function __invoke(
        Mix $mix,
        PlaybackService $playbackService,
        Request $request
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);

        // Check for throttling conditions
        if ($playbackService->shouldThrottleCommand($mix)) {
            Log::info("Throttling resume command for mix {$mix->id}");
            return response()->json([
                'success' => true,
                'is_playing' => true,
                'action' => 'resume',
                'throttled' => true
            ]);
        }

        try {
            // RESUME FLOW:
            // 1. Find the appropriate song to resume
            // 2. Determine resume position
            // 3. Call Spotify API to resume playback
            // 4. Update state and broadcast to clients
            $deviceId = $request->input('device_id');
            $result = $playbackService->resumeMixPlayback($mix, $deviceId);

            return response()->json($result);
        } catch (Exception $e) {
            Log::error("Error resuming playback: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume playback: ' . $e->getMessage()
            ]);
        }
    }
}
