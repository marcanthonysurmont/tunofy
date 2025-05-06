<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\SongPlaybackService;

class PlayNextSongController extends Controller
{
    public function __invoke(Mix $mix, SongPlaybackService $songPlaybackService)
    {
        $this->authorize('update', $mix);
        
        $result = $songPlaybackService->advanceToNextSong($mix->id);

        return response()->json($result);
    }
}
