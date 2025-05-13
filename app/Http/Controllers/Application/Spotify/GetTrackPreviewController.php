<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Requests\GetTrackPreviewRequest;
use App\Services\SpotifyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GetTrackPreviewController extends Controller
{
    public function __invoke(GetTrackPreviewRequest $request, SpotifyService $spotifyService): JsonResponse
    {
        $validated = $request->validated();

        $previewUrl = $spotifyService->getPreviewUrl($validated['track_id']);

        if ($previewUrl) {
            return response()->json(['preview_url' => $previewUrl]);
        }

        return response()->json(['error' => 'Preview not available'], 404);
    }
}
