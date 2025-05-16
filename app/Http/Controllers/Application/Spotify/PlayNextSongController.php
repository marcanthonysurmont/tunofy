<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\SongPlaybackService;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\MixStatusChangedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\SpotifyService;
use App\Services\PlaybackStateManager;

class PlayNextSongController extends Controller
{
    public function __invoke(
        Mix $mix,
        SongPlaybackService $songPlaybackService,
        SpotifyService $spotifyService,
        PlaybackStateManager $playbackStateManager
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);

        // 1. Get the next song data first (fast database query)
        $nextSong = $songPlaybackService->getNextSongToPlay($mix->id);

        // Check if queue is completed
        if (!$nextSong) {
            Log::info("Queue completed for mix {$mix->id}");

            // Set mix inactive and broadcast
            $mix->update(['is_active' => false]);
            event(new MixStatusChangedEvent($mix, false, 'queue_completed'));
            Cache::put("mix:{$mix->id}:queue_completed", true, now()->addHours(1));

            return response()->json([
                'success' => false,
                'queue_completed' => true,
                'message' => 'Queue completed'
            ]);
        }

        // 2. Mark current song as finished and next song as playing (fast database updates)
        $currentSong = $songPlaybackService->getCurrentlyPlayingSong($mix);
        if ($currentSong) {
            $currentSong->update(['status' => 'finished', 'played_at' => now()]);
        }

        // 3. Send playback instruction to Spotify (the slow part)
        $deviceId = $playbackStateManager->getDeviceId($mix);
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        // 4. Create playback data structure based on next song
        $playbackData = [
            'is_playing' => true,
            'item' => [
                'id' => $nextSong->song->spotify_id,
                'name' => $nextSong->song->name,
                'duration_ms' => $nextSong->song->duration_ms,
                'artists' => [['name' => $nextSong->song->artist]],
                'album' => [
                    'images' => [['url' => $nextSong->song->image_url]]
                ]
            ],
            '_timestamp' => now()->timestamp,
            '_action' => 'skip'
        ];

        // 5. Mark the next song as playing
        $nextSong->update(['status' => 'playing']);

        // 6. Update cache and broadcast IMMEDIATELY
        $cacheKey = "mix:playback:" . $mix->id;
        Cache::put($cacheKey, $playbackData);
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        // 7. Send play command to Spotify AFTER broadcasting
        try {
            $spotifyService->playTrackOnDevice(
                $user,
                $nextSong->song->spotify_id,
                $deviceId
            );

            // Set manual change flag to prevent polling override
            $playbackStateManager->setManualChange($mix);

            // Return success response
            return response()->json([
                'success' => true,
                'song' => [
                    'id' => $nextSong->song->id,
                    'spotify_id' => $nextSong->song->spotify_id,
                    'name' => $nextSong->song->name,
                    'artist' => $nextSong->song->artist,
                    'duration_ms' => $nextSong->song->duration_ms,
                    'image_url' => $nextSong->song->image_url
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error playing track: " . $e->getMessage());

            // Even if Spotify play fails, we've already updated the UI
            return response()->json([
                'success' => true,
                'song' => [
                    'spotify_id' => $nextSong->song->spotify_id,
                    'name' => $nextSong->song->name,
                    'artist' => $nextSong->song->artist,
                    'duration_ms' => $nextSong->song->duration_ms,
                    'image_url' => $nextSong->song->image_url
                ]
            ]);
        }
    }
}
