<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use Exception;
use App\Http\Requests\ImportSpotifyPlaylistRequest;
use App\Models\Mix;
use App\Services\Spotify\SpotifyService;
use Illuminate\Support\Facades\Auth;

class ImportSpotifyPlaylistController extends Controller
{
    public function __invoke(ImportSpotifyPlaylistRequest $request, Mix $mix, SpotifyService $spotifyService)
    {
        $validated = $request->validated();

        $user = Auth::user();

        try {
            $existingTrackIds = $mix->songs()->pluck('spotify_id')->toArray();

            $playlistSongs = $spotifyService->getPlaylistTracks($user, $validated['playlist_id'], $existingTrackIds);

            // Count imported songs
            $songsCount = count($playlistSongs);

            // If no songs to import, return early with appropriate message
            if ($songsCount === 0) {
                return redirect()->back()
                    ->with('error', 'No new songs to import. All tracks from this playlist are already in your mix.');
            }

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

            $mix->update(['mix_count' => $mix->mix_count + $songsCount]);

            $successMessage = $songsCount === 1
                ? '1 song imported successfully!'
                : "{$songsCount} songs imported successfully!";

            return redirect()->back()
                ->with('success', $successMessage);
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to import playlist: ' . $e->getMessage());
        }
    }
}
