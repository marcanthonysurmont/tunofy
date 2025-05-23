<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddSongToMixRequest;
use App\Models\GlobalUserStat;
use App\Models\Mix;
use App\Models\Song;
use App\Models\UserSongHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Exception;
use Illuminate\Support\Facades\DB;

class AddSongToMixController extends Controller
{
    public function __invoke(AddSongToMixRequest $request, Mix $mix): RedirectResponse
    {
        $this->authorize('addSongs', $mix);

        $validated = $request->validated();

        try {
            $user = Auth::user();

            Song::create([
                'mix_id' => $mix->id,
                'spotify_id' => $validated['spotify_id'],
                'user_id' => $user->id,
                'duration_ms' => $validated['duration_ms'],
                'last_fetched_at' => now(),
                'name' => $validated['name'],
                'artist' => $validated['artist'],
                'image_url' => $validated['image_url'],
            ]);

            $mix->update(['mix_count' => $mix->mix_count + 1]);

            GlobalUserStat::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'songs_added' => DB::raw('songs_added + 1'),
                ],
            );

            UserSongHistory::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'spotify_id' => $validated['spotify_id'],
                ],
                [
                    'song_name' => $validated['name'],
                    'artist' => $validated['artist'],
                    'times_added' => DB::raw('times_added + 1'),
                ],
            );


            return redirect()->back()
                ->with('success', 'Song added to mix successfully.');
        } catch (Exception $e) {

            if($e->getCode() == 23000) {
                return redirect()->back()
                    ->with('error', 'Song already exists in the mix.');
            }

            return redirect()->back()
                ->with('error', 'Failed to add song to mix');
        }
    }
}
