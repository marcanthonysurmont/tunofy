<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\RedirectResponse;

class RemoveSongFromMixController extends Controller
{
    public function __invoke(Song $song): RedirectResponse
    {
        $this->authorize('removeSongs', $song->mix);

        try {
            $song->delete();
    
            return redirect()->back()
                ->with('success', 'Song removed from mix successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to remove song from mix');
        }
    }
}
