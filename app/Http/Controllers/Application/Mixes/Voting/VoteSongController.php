<?php

namespace App\Http\Controllers\Application\Mixes\Voting;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoteSongRequest;
use App\Models\Vote;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\QueueSong;

class VoteSongController extends Controller
{
    public function __invoke(QueueSong $queueSong, VoteSongRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            Vote::create([
                'queue_song_id' => $queueSong->id,
                'user_id' => Auth::id(),
                'vote_type' => $validated['vote_type'],
            ]);

            // Update the vote counts for the current song
            if ($validated['vote_type'] === 'like') {
                $queueSong->increment('like_count');
            }

            if ($validated['vote_type'] === 'dislike') {
                $queueSong->increment('dislike_count');
            }

            if ($validated['vote_type'] === 'kill') {
                $queueSong->increment('kill_count');
            }

            $mix = $queueSong->mix;
            $queueSong->refresh();
            $pendingSongs = $mix->getAllPendingSongs()->fresh();
            $preset = $mix->preset;

            // Make sure we're sorting correctly
            $sortedSongs = $pendingSongs->sortByDesc(function ($song) {
                return $song->like_count - $song->dislike_count;
            });

            $collaboratorsCount = $mix->collaborators()->count() + 1;

            // Update the order field for each song
            $order = 1;
            foreach ($sortedSongs as $song) {
                $songKillCount = $song->kill_count;

                if($songKillCount > 0 && ($songKillCount / $collaboratorsCount) * 100 >= $preset->kill_percentage) {
                    $song->update([
                        'status' => 'finished', 
                        'is_killed' => true
                    ]);
                }
                
                // Avoid unnecessary DB updates if order hasn't changed
                if ($song->order !== $order) {
                    $song->update(['order' => $order]);
                }
                $order++;
            }

            return redirect()->back();
        } catch (Exception $e) {
            Log::error("Error updating song order: " . $e->getMessage());
            return redirect()->back();
        }
    }
}
