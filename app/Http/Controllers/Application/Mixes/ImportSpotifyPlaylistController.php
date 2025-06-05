<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\GlobalUserStat;
use App\Models\MixUserStat;
use App\Models\UserSongHistory;
use Exception;
use App\Http\Requests\ImportSpotifyPlaylistRequest;
use App\Models\Mix;
use App\Services\Spotify\SpotifyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Events\ImportedPlaylistEvent;

class ImportSpotifyPlaylistController extends Controller
{
    public function __invoke(ImportSpotifyPlaylistRequest $request, Mix $mix, SpotifyService $spotifyService)
    {
        $validated = $request->validated();

        $user = Auth::user();

        try {
            $existingTrackIds = $mix->songs()->pluck('spotify_id')->toArray();

            $playlistSongs = $spotifyService->getPlaylistTracks($user, $validated['playlist_id'], $existingTrackIds);

            // Count imported songs
            $songsCount = count($playlistSongs);

            // If no songs to import, return early with appropriate message
            if ($songsCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No new songs to import. All tracks from this playlist are already in your mix.',
                    'imported_songs' => []
                ]);
            }

            // Prepare data for batch operations
            $songsToInsert = [];
            $spotifyIds = [];
            $now = now();

            foreach ($playlistSongs as $song) {
                $artistNames = collect($song['track']['artists'])
                    ->pluck('name')
                    ->filter()
                    ->join(', ');

                $songsToInsert[] = [
                    'mix_id' => $mix->id,
                    'spotify_id' => $song['track']['id'],
                    'user_id' => $user->id,
                    'duration_ms' => $song['track']['duration_ms'],
                    'last_fetched_at' => $now,
                    'name' => $song['track']['name'],
                    'artist' => $artistNames,
                    'image_url' => $song['track']['album']['images']['0']['url'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $spotifyIds[] = $song['track']['id'];
            }

            // Use createMany which returns models with IDs
            $insertedSongs = $mix->songs()->createMany($songsToInsert);

            // Set the user relation on each song model
            foreach ($insertedSongs as $song) {
                $song->setRelation('user', $user);
            }

            $limitedInsertedSongs = $insertedSongs->take(15);

            $hasMore = count($insertedSongs) > 15;

            ImportedPlaylistEvent::dispatch($mix, $limitedInsertedSongs, $hasMore);

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url ?? null,
                'role' => $user->role ?? null,
                'type' => $user->type ?? null,
            ];

            $importedSongs = [];
            foreach ($insertedSongs as $song) {
                $songArr = $song->toArray();
                $songArr['user'] = $userData;
                $importedSongs[] = $songArr;
            }

            // Efficiently handle UserSongHistory updates
            if (!empty($spotifyIds)) {
                // Get existing records to determine which ones to update vs insert
                $existingRecords = UserSongHistory::where('user_id', $user->id)
                    ->whereIn('spotify_id', $spotifyIds)
                    ->get(['id', 'spotify_id']);

                // Update existing records in a single query if any exist
                if ($existingRecords->isNotEmpty()) {
                    $existingIds = $existingRecords->pluck('id')->toArray();
                    UserSongHistory::whereIn('id', $existingIds)
                        ->update([
                            'times_added' => DB::raw('times_added + 1'),
                            'updated_at' => $now
                        ]);
                }

                // Prepare new records for insertion
                $existingSpotifyIds = $existingRecords->pluck('spotify_id')->toArray();
                $newHistoryRecords = [];

                // Create a mapping of spotify_id to song details for easy lookup
                $songDetails = [];
                foreach ($playlistSongs as $song) {
                    $artistNames = collect($song['track']['artists'])
                        ->pluck('name')
                        ->filter()
                        ->join(', ');

                    $songDetails[$song['track']['id']] = [
                        'name' => $song['track']['name'],
                        'artist' => $artistNames,
                        'image_url' => $song['track']['album']['images'][0]['url'] ?? null,
                    ];
                }

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

                // Batch insert new history records if any
                if (!empty($newHistoryRecords)) {
                    UserSongHistory::insert($newHistoryRecords);
                }
            }

            $mix->update(['mix_count' => $mix->mix_count + $songsCount]);

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

            $successMessage = $songsCount === 1
                ? '1 song imported successfully!'
                : "{$songsCount} songs imported successfully!";

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'imported_songs' => $importedSongs,
            ]);
        } catch (Exception $e) {
            \Log::error($e->getMessage());
            ds('Error importing Spotify playlist: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to import playlist: ' . $e->getMessage());
        }
    }
}
