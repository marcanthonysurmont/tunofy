<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\SongPlaybackService;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\SpotifyService;
use App\Services\PlaybackStateManager;

class PlayPreviousSongController extends Controller
{
    public function __invoke(
        Mix $mix,
        SongPlaybackService $songPlaybackService,
        SpotifyService $spotifyService,
        PlaybackStateManager $playbackStateManager
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);

        // 1. Get the current song that's playing
        $currentSong = $songPlaybackService->getCurrentlyPlayingSong($mix);
        if (!$currentSong) {
            return response()->json([
                'success' => false,
                'message' => 'No song is currently playing'
            ]);
        }

        // 2. Get the previous song from the same session
        $previousSong = $songPlaybackService->getPreviousSong($mix, $currentSong);
        if (!$previousSong) {
            return response()->json([
                'success' => false,
                'message' => 'Already at the first song in this session'
            ]);
        }

        // 3. Update database status (fast operations)
        $currentSong->update(['status' => 'pending']);
        $previousSong->update(['status' => 'playing']);

        // 4. Create playback data structure based on previous song
        $playbackData = [
            'is_playing' => true,
            'item' => [
                'id' => $previousSong->song->spotify_id,
                'name' => $previousSong->song->name,
                'duration_ms' => $previousSong->song->duration_ms,
                'artists' => [['name' => $previousSong->song->artist]],
                'album' => [
                    'images' => [['url' => $previousSong->song->image_url]]
                ]
            ],
            '_timestamp' => now()->timestamp,
            '_action' => 'previous' // Add action type for frontend
        ];

        // 5. Update cache and broadcast IMMEDIATELY
        $cacheKey = "mix:playback:" . $mix->id;
        Cache::put($cacheKey, $playbackData);
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        // 6. Send play command to Spotify AFTER broadcasting
        try {
            // Get device ID if needed
            $deviceId = $playbackStateManager->getDeviceId($mix);
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

            // Tell Spotify to play this song
            $spotifyService->playTrackOnDevice(
                $user,
                $previousSong->song->spotify_id,
                $deviceId
            );

            // Set manual change flag to prevent polling override
            $playbackStateManager->setManualChange($mix);

            // Return success response with song data
            return response()->json([
                'success' => true,
                'song' => [
                    'id' => $previousSong->song->id,
                    'spotify_id' => $previousSong->song->spotify_id,
                    'name' => $previousSong->song->name,
                    'artist' => $previousSong->song->artist,
                    'duration_ms' => $previousSong->song->duration_ms,
                    'image_url' => $previousSong->song->image_url
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error playing previous track: " . $e->getMessage());

            // Even if Spotify play fails, we've already updated the UI
            return response()->json([
                'success' => true,
                'song' => [
                    'spotify_id' => $previousSong->song->spotify_id,
                    'name' => $previousSong->song->name,
                    'artist' => $previousSong->song->artist,
                    'duration_ms' => $previousSong->song->duration_ms,
                    'image_url' => $previousSong->song->image_url
                ]
            ]);
        }
    }
}
