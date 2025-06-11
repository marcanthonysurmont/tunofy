<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchSongRequest;
use App\Http\Resources\SpotifySearchResource;
use App\Services\Spotify\SpotifyService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class SearchSongController extends Controller
{
    public function __invoke(SearchSongRequest $request, SpotifyService $spotifyService): JsonResponse
    {
        try {
            $validated = $request->validated();

            $response = $spotifyService->search($validated['query']);

            return response()->json([
                'songs' => SpotifySearchResource::collection($response['tracks']['items']),
            ]);
        } catch (Exception $e) {
            // Handle the exception, log it, or return an error response
            Log::error('Spotify API request failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Spotify API request failed',
            ]);
        }
    }
}
