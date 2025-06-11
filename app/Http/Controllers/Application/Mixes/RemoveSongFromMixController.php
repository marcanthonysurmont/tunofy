<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Events\SongDeletedEvent;
use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\RedirectResponse;
use Exception;

class RemoveSongFromMixController extends Controller
{
    public function __invoke(Song $song): RedirectResponse
    {
        $this->authorize('removeSongs', $song->mix);

        try {
            $mix = $song->mix;

            $song->delete();

            SongDeletedEvent::dispatch($mix, $song);

            $mix->update(['mix_count' => $mix->mix_count - 1]);
    
            return redirect()->back()
                ->with('success', 'Song removed from mix successfully.');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to remove song from mix');
        }
    }
}
