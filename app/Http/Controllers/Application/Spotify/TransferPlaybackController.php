<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class TransferPlaybackController extends Controller
{
    public function __invoke(Mix $mix, Request $request, PlaybackService $playbackService): JsonResponse
    {
        $this->authorize('controlPlayback', $mix);

        // Validate device ID
        $deviceId = $request->input('deviceId');
        if (!$deviceId) {
            return response()->json(['error' => 'Device ID is required'], 400);
        }

        try {
            // TRANSFER FLOW:
            // 1. Find current playing song and progress
            // 2. Activate the new device
            // 3. Play the current song at current position
            // 4. Update device state and broadcast updates
            $result = $playbackService->transferPlayback($mix, $deviceId);
            
            return response()->json($result);
        } catch (Exception $e) {
            Log::error("Error transferring playback: " . $e->getMessage());
            return response()->json(['error' => 'Error transferring playback: ' . $e->getMessage()], 500);
        }
    }
}
