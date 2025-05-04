<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Requests\RequestSpotifyPlayerStatusRequest;
use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\PlaybackService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class RequestSpotifyPlayerStatusController extends Controller
{
    public function __invoke(RequestSpotifyPlayerStatusRequest $request, PlaybackService $playbackService): JsonResponse {
        
        $validated = $request->validated();

        $requestId = substr(md5(now()->timestamp . rand()), 0, 6);

        // Get mix and authorize access
        $mix = Mix::findOrFail($validated['mix_id']);

        $this->authorize('view', $mix);

        // Log the request
        Log::info("[REQ-{$requestId}] Status request for mix {$mix->id}, is_active: " .
            ($mix->is_active ? 'yes' : 'no'));

        // Basic response always includes these fields
        $response = [
            'success' => true,
            'mix_id' => $mix->id,
            'is_active' => (bool) $mix->is_active,
            'timestamp' => now()->toIso8601String(),
        ];

        // Only get playback data if mix is active
        if ($mix->is_active) {
            $playbackData = $playbackService->getPlaybackData($mix, $requestId);

            if (isset($playbackData['error'])) {
                $response['playback_error'] = $playbackData['error'];
            } else {
                $response['playback_data'] = $playbackData;
            }
        }

        return response()->json($response);
    }
}
