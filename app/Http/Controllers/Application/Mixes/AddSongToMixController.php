<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddSongToMixRequest;
use App\Models\Mix;
use App\Models\Song;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class AddSongToMixController extends Controller
{
    public function __invoke(AddSongToMixRequest $request, Mix $mix): RedirectResponse
    {
        $this->authorize('addSong', $mix);
        
        $validated = $request->validated();

        try {
            Song::create([
                'mix_id' => $mix->id,
                'spotify_id' => $validated['spotify_id'],
                'user_id' => Auth::id(),
                'duration_ms' => $validated['duration_ms'],
                'last_fetched_at' => now(),
                'name' => $validated['name'],
                'artist' => $validated['artist'],
                'image_url' => $validated['image_url'],
            ]);
    
            return redirect()->back()
                ->with('success', 'Song added to mix successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to add song to mix');
        }
    }
}
