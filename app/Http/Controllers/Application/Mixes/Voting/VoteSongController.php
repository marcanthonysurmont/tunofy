<?php

namespace App\Http\Controllers\Application\Mixes\Voting;

use App\Http\Controllers\Controller;
use App\Http\Requests\VoteSongRequest;
use App\Models\GlobalUserStat;
use App\Models\MixUserStat;
use App\Models\Vote;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\QueueSong;
use App\Events\VoteUpdatedEvent;
use Illuminate\Support\Facades\DB;
use App\Models\MixStat;

class VoteSongController extends Controller
{
    public function __invoke(QueueSong $queueSong, VoteSongRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $user = Auth::user();

            Vote::create([
                'queue_song_id' => $queueSong->id,
                'user_id' => $user->id,
                'vote_type' => $validated['vote_type'],
            ]);

            // Update the vote counts for the current song
            if ($validated['vote_type'] === 'like') {
                $queueSong->increment('like_count');

                GlobalUserStat::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'like_count' => DB::raw('like_count + 1'),
                        'total_votes' => DB::raw('total_votes + 1'),
                    ],
                );

                MixUserStat::updateOrCreate(
                    [
                        'mix_id' => $queueSong->mix_id,
                        'user_id' => $user->id,
                    ],
                    [
                        'songs_liked' => DB::raw('songs_liked + 1'),
                        'total_votes' => DB::raw('total_votes + 1'),
                    ]
                );

                MixStat::updateOrCreate(
                    [
                        'mix_id' => $queueSong->mix_id,
                    ],
                    [
                        'songs_liked' => DB::raw('songs_liked + 1'),
                        'total_votes' => DB::raw('total_votes + 1'),
                    ]
                );
            }

            if ($validated['vote_type'] === 'dislike') {
                $queueSong->increment('dislike_count');

                GlobalUserStat::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'dislike_count' => DB::raw('dislike_count + 1'),
                        'total_votes' => DB::raw('total_votes + 1'),
                    ]
                );

                MixUserStat::updateOrCreate(
                    [
                        'mix_id' => $queueSong->mix_id,
                        'user_id' => $user->id,
                    ],
                    [
                        'songs_disliked' => DB::raw('songs_disliked + 1'),
                        'total_votes' => DB::raw('total_votes + 1'),
                    ]
                );

                MixStat::updateOrCreate(
                    [
                        'mix_id' => $queueSong->mix_id,
                    ],
                    [
                        'songs_disliked' => DB::raw('songs_disliked + 1'),
                        'total_votes' => DB::raw('total_votes + 1'),
                    ]
                );
            }

            if ($validated['vote_type'] === 'kill') {
                $queueSong->increment('kill_count');

                GlobalUserStat::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'kill_count' => DB::raw('kill_count + 1'),
                        'total_votes' => DB::raw('total_votes + 1'),
                    ]
                );

                MixUserStat::updateOrCreate(
                    [
                        'mix_id' => $queueSong->mix_id,
                        'user_id' => $user->id,
                    ],
                    [
                        'songs_killed' => DB::raw('songs_killed + 1'),
                        'total_votes' => DB::raw('total_votes + 1'),
                    ]
                );

                MixStat::updateOrCreate(
                    [
                        'mix_id' => $queueSong->mix_id,
                    ],
                    [
                        'songs_killed' => DB::raw('songs_killed + 1'),
                        'total_votes' => DB::raw('total_votes + 1'),
                    ]
                );
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
                        'is_killed' => true
                    ]);
                }
                
                // Avoid unnecessary DB updates if order hasn't changed
                if ($song->order !== $order) {
                    $song->update(['order' => $order]);
                }
                $order++;
            }

            VoteUpdatedEvent::dispatch($mix);

            return redirect()->back();
        } catch (Exception $e) {
            Log::error("Error updating song order: " . $e->getMessage());
            return redirect()->back();
        }
    }
}
