<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Services\Songs\SongService;
use Exception;
use App\Http\Requests\ImportSpotifyPlaylistRequest;
use App\Models\Mix;
use App\Services\Spotify\SpotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImportSpotifyPlaylistController extends Controller
{
    public function __invoke(
        ImportSpotifyPlaylistRequest $request,
        Mix $mix,
        SpotifyService $spotifyService,
        SongService $songService
    ): JsonResponse {
        $this->authorize('addSongs', $mix);

        try {
            $validated = $request->validated();
            $user = Auth::user();

            $existingTrackIds = $mix->songs()->pluck('spotify_id')->toArray();

            $playlistSongs = $spotifyService->getPlaylistTracks($user, $validated['playlist_id'], $existingTrackIds);

            // If no songs to import, return early with appropriate message
            if (count($playlistSongs) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No new songs to import. All tracks from this playlist are already in your mix.',
                    'imported_songs' => []
                ]);
            }

            // Process songs in batch
            $result = $songService->importSpotifySongsBatch(
                $mix,
                $user,
                $playlistSongs
            );

            // Create success message
            $successMessage = $result['count'] === 1
                ? '1 song imported successfully!'
                : "{$result['count']} songs imported successfully!";

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'imported_songs' => $result['songs']
            ]);

        } catch (Exception $e) {
            Log::error("Failed to import Spotify playlist: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to import playlist: ' . $e->getMessage()
            ], 500);
        }
    }
}
