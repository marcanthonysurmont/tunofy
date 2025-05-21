<?php

namespace App\Http\Controllers\Application\Mixes\Voting;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoteSongRequest;
use App\Models\Vote;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VoteSongController extends Controller
{
    public function __invoke(VoteSongRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            Vote::create([
                'queue_song_id' => $validated['queue_song_id'],
                'user_id' => Auth::id(),
                'vote_type' => $validated['vote_type'],
            ]);

            return redirect()->back();
        } catch (Exception $e) {
            return redirect()->back();
        } 
    }
}
