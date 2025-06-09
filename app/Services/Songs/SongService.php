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
use App\Events\ImportedPlaylistEvent;
use App\Models\User;

class SongService
{
    /**
     * Create song record and update related statistics
     */
    public function createSongWithStats(Mix $mix, array $songData, User $user): Song
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

    /**
     * Import a batch of songs from Spotify
     */
    public function importSpotifySongsBatch(Mix $mix, $user, array $playlistSongs): array
    {
        $songsToInsert = [];
        $spotifyIds = [];
        $now = now();

        // Prepare song data
        foreach ($playlistSongs as $song) {
            $artistNames = $this->formatArtistNames($song['track']['artists']);

            $songsToInsert[] = [
                'mix_id' => $mix->id,
                'spotify_id' => $song['track']['id'],
                'user_id' => $user->id,
                'duration_ms' => $song['track']['duration_ms'],
                'last_fetched_at' => $now,
                'name' => $song['track']['name'],
                'artist' => $artistNames,
                'image_url' => $song['track']['album']['images'][0]['url'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $spotifyIds[] = $song['track']['id'];
        }

        // Create songs and get models with IDs
        $insertedSongs = $mix->songs()->createMany($songsToInsert);

        // Set user relation for each song
        foreach ($insertedSongs as $song) {
            $song->setRelation('user', $user);
        }

        $limitedInsertedSongs = $insertedSongs->take(15);
        $hasMore = count($insertedSongs) > 15;

        // Dispatch event for real-time updates
        ImportedPlaylistEvent::dispatch($mix, $limitedInsertedSongs, $hasMore);

        // Format user data for song relationships
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url ?? null,
            'role' => $user->role ?? null,
            'type' => $user->type ?? null,
        ];

        // Format songs for response
        $importedSongs = [];
        foreach ($insertedSongs as $song) {
            $songArr = $song->toArray();
            $songArr['user'] = $userData;
            $importedSongs[] = $songArr;
        }

        // Update user song history
        $this->updateUserSongHistory($user, $spotifyIds, $playlistSongs);

        // Update stats
        $songsCount = count($insertedSongs);
        $mix->update(['mix_count' => $mix->mix_count + $songsCount]);

        $this->updateStats($mix, $user, $songsCount);

        return [
            'songs' => $importedSongs,
            'count' => $songsCount
        ];
    }

    /**
     * Format artist names from Spotify data
     */
    private function formatArtistNames(array $artists): string
    {
        return collect($artists)
            ->pluck('name')
            ->filter()
            ->join(', ');
    }

    /**
     * Update user song history when importing songs
     */
    private function updateUserSongHistory($user, array $spotifyIds, array $playlistTracks): void
    {
        if (empty($spotifyIds)) {
            return;
        }

        $now = now();

        // Get existing records
        $existingRecords = UserSongHistory::where('user_id', $user->id)
            ->whereIn('spotify_id', $spotifyIds)
            ->get(['id', 'spotify_id']);

        // Update existing records
        if ($existingRecords->isNotEmpty()) {
            $existingIds = $existingRecords->pluck('id')->toArray();
            UserSongHistory::whereIn('id', $existingIds)
                ->update([
                    'times_added' => DB::raw('times_added + 1'),
                    'updated_at' => $now
                ]);
        }

        // Create mapping of spotify_id to song details
        $songDetails = [];
        foreach ($playlistTracks as $song) {
            $artistNames = $this->formatArtistNames($song['track']['artists']);

            $songDetails[$song['track']['id']] = [
                'name' => $song['track']['name'],
                'artist' => $artistNames,
                'image_url' => $song['track']['album']['images'][0]['url'] ?? null,
            ];
        }

        // Prepare new records
        $existingSpotifyIds = $existingRecords->pluck('spotify_id')->toArray();
        $newHistoryRecords = [];

        foreach ($spotifyIds as $spotifyId) {
            if (!in_array($spotifyId, $existingSpotifyIds)) {
                $newHistoryRecords[] = [
                    'user_id' => $user->id,
                    'song_name' => $songDetails[$spotifyId]['name'],
                    'artist' => $songDetails[$spotifyId]['artist'],
                    'spotify_id' => $spotifyId,
                    'image_url' => $songDetails[$spotifyId]['image_url'],
                    'times_added' => 1,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
        }

        // Insert new records
        if (!empty($newHistoryRecords)) {
            UserSongHistory::insert($newHistoryRecords);
        }
    }

    /**
     * Update global and mix-specific stats
     */
    private function updateStats(Mix $mix, $user, int $songsCount): void
    {
        GlobalUserStat::updateOrCreate(
            ['user_id' => $user->id],
            ['songs_added' => DB::raw('songs_added + ' . $songsCount)]
        );

        MixUserStat::updateOrCreate(
            [
                'mix_id' => $mix->id,
                'user_id' => $user->id,
            ],
            ['songs_added' => DB::raw('songs_added + ' . $songsCount)]
        );
    }
}
