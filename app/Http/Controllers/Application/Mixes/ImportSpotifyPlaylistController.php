<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportSpotifyPlaylistRequest;
use App\Models\Mix;
use App\Services\SpotifyService;
use Illuminate\Support\Facades\Auth;

class ImportSpotifyPlaylistController extends Controller
{
    public function __invoke(ImportSpotifyPlaylistRequest $request, Mix $mix, SpotifyService $spotifyService)
    {
        $validated = $request->validated();

        $user = Auth::user();

        $playlistSongs = $spotifyService->getPlaylistTracks($user, $validated['playlist_id']);

        foreach ($playlistSongs as $song) {            
            $artistNames = collect($song['track']['artists'])
                ->pluck('name')
                ->filter()
                ->join(', ');

            $mix->songs()->create([
                'spotify_id' => $song['track']['id'],
                'user_id' => $user->id,
                'duration_ms' => $song['track']['duration_ms'],
                'last_fetched_at' => now(),
                'name' => $song['track']['name'],
                'artist' => $artistNames,
                'image_url' => $song['track']['album']['images']['0']['url'],
            ]);
        }

        $mix->update(['mix_count' => $mix->mix_count + collect($playlistSongs)->count()]);

        return redirect()->back()
            ->with('success', 'Playlist imported successfully!');
    }
}
