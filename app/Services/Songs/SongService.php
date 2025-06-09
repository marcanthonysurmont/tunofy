<?php

namespace App\Services\Songs;

use App\Events\SongAddedEvent;
use App\Events\StatUpdatedEvent;
use App\Models\GlobalUserStat;
use App\Models\Mix;
use App\Models\MixUserStat;
use App\Models\Song;
use App\Models\UserSongHistory;
use Illuminate\Support\Facades\DB;

class SongService
{
    /**
     * Create song record and update related statistics
     */
    public function createSongWithStats(Mix $mix, array $songData, $user): Song
    {
        // Create the song record
        $song = Song::create([
            'mix_id' => $mix->id,
            'spotify_id' => $songData['spotify_id'],
            'user_id' => $user->id,
            'duration_ms' => $songData['duration_ms'],
            'last_fetched_at' => now(),
            'name' => $songData['name'],
            'artist' => $songData['artist'],
            'image_url' => $songData['image_url'],
        ]);

        // Increment mix count
        $mix->update(['mix_count' => $mix->mix_count + 1]);

        // Set relation for event and dispatch
        $song->setRelation('user', $user);
        SongAddedEvent::dispatch($mix, $song);

        // Update global user stats
        GlobalUserStat::updateOrCreate(
            ['user_id' => $user->id],
            ['songs_added' => DB::raw('songs_added + 1')]
        );

        // Update user song history
        UserSongHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'spotify_id' => $songData['spotify_id'],
            ],
            [
                'song_name' => $songData['name'],
                'artist' => $songData['artist'],
                'image_url' => $songData['image_url'],
                'times_added' => DB::raw('times_added + 1'),
            ]
        );

        // Update mix-specific user stats
        MixUserStat::updateOrCreate(
            [
                'mix_id' => $mix->id,
                'user_id' => $user->id,
            ],
            ['songs_added' => DB::raw('songs_added + 1')]
        );

        StatUpdatedEvent::dispatch($mix);

        return $song;
    }
}
