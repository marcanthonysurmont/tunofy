<?php

namespace App\Services\Songs;

use App\Models\GlobalUserStat;
use App\Models\MixStat;
use App\Models\MixUserStat;
use App\Models\QueueSong;
use Illuminate\Support\Facades\DB;
use App\Models\Vote;

class VotingService
{
    /**
     * Update all stats tables for a vote
     */
    public function updateStats(int $mixId, int $userId, string $voteType): void
    {
        // Update global user stats
        GlobalUserStat::updateOrCreate(
            ['user_id' => $userId],
            [
                "{$voteType}_count" => DB::raw("{$voteType}_count + 1"),
                'total_votes' => DB::raw('total_votes + 1'),
            ]
        );

        // Update mix user stats
        MixUserStat::updateOrCreate(
            [
                'mix_id' => $mixId,
                'user_id' => $userId,
            ],
            [
                "songs_{$voteType}d" => DB::raw("songs_{$voteType}d + 1"),
                'total_votes' => DB::raw('total_votes + 1'),
            ]
        );

        // Update mix stats
        MixStat::updateOrCreate(
            ['mix_id' => $mixId],
            [
                "songs_{$voteType}d" => DB::raw("songs_{$voteType}d + 1"),
                'total_votes' => DB::raw('total_votes + 1'),
            ]
        );
    }

    /**
     * Update queue order and check for killed songs
     */
    public function updateQueueOrder(QueueSong $queueSong): void
    {
        $mix = $queueSong->mix;
        $queueSong->refresh();
        $pendingSongs = $mix->getAllPendingSongs()->fresh();
        $preset = $mix->preset;

        // Sort songs by net vote count (likes minus dislikes)
        $sortedSongs = $pendingSongs->sortByDesc(function ($song) {
            return $song->like_count - $song->dislike_count;
        });

        $collaboratorsCount = $mix->collaborators()->count() + 1;

        // Update order and check for killed songs
        $order = 1;
        foreach ($sortedSongs as $song) {
            // Check if song should be killed
            $songKillCount = $song->kill_count;
            if ($songKillCount > 0 &&
                ($songKillCount / $collaboratorsCount) * 100 >= $preset->kill_percentage) {
                $song->update(['is_killed' => true]);
            }

            // Update order if changed
            if ($song->order !== $order) {
                $song->update(['order' => $order]);
            }
            $order++;
        }
    }

    public function processVote(QueueSong $queueSong, int $userId, string $voteType): void
    {
        // Create the vote record
        Vote::create([
            'queue_song_id' => $queueSong->id,
            'user_id' => $userId,
            'vote_type' => $voteType,
        ]);

        // Update vote count on the queue song
        $queueSong->increment("{$voteType}_count");

        // Update all stats tables
        $this->updateStats($queueSong->mix_id, $userId, $voteType);
    }
}
