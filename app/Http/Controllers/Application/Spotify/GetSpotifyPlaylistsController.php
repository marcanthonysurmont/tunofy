<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Services\Spotify\SpotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GetSpotifyPlaylistsController extends Controller
{
    public function __invoke(SpotifyService $spotifyService): JsonResponse
    {
        $playlists = $spotifyService->getUserPlaylists(Auth::user());

        return response()->json($playlists);
    }
}
