<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\Playback\PlaybackService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\SpotifyPlaybackResource;

class RequestSpotifyPlayerStatusController extends Controller
{
    public function __invoke(Mix $mix, PlaybackService $playbackService): JsonResponse
    {
        $this->authorize('view', $mix);

        // Basic response always includes these fields
        $response = [
            'success' => true,
            'mix_id' => $mix->id,
            'is_active' => (bool) $mix->is_active,
            'timestamp' => now()->toIso8601String(),
        ];

        // Return early if mix is not active
        if (!$mix->is_active) {
            return response()->json($response);
        }

        // Get playback data since mix is active
        $playbackData = $playbackService->getPlaybackData($mix);

        // Handle error case
        if (isset($playbackData['error'])) {
            $response['playback_error'] = $playbackData['error'];

            return response()->json($response);
        }

        // Add playback data to response
        $response['playback_data'] = new SpotifyPlaybackResource($playbackData);

        return response()->json($response);
    }
}
