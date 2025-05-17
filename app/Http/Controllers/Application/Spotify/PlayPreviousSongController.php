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

        // 5. Update cache via PlaybackStateManager and broadcast IMMEDIATELY
        $playbackStateManager->setPlaybackData($mix, $playbackData);
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        // 6. Send play command to Spotify AFTER broadcasting
        try {
            // Get device ID if needed
            $deviceId = $playbackStateManager->getDeviceId($mix);
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

            // Ensure the device is actually available on Spotify's side
            if ($deviceId) {
                // Set a device change grace period
                Cache::put("mix:{$mix->id}:device_changed", true, now()->addSeconds(5));

                // Try to activate the device first
                $activationSuccess = $spotifyService->activateDevice($user, $deviceId);

                if (!$activationSuccess) {
                    // If device activation fails, try to get active devices and pick one
                    $availableDevices = $spotifyService->getUserDevices($user);
                    if (!empty($availableDevices)) {
                        // Use first active device or first available if none active
                        $activeDevice = collect($availableDevices)->firstWhere('is_active', true);
                        $deviceId = $activeDevice ? $activeDevice['id'] : $availableDevices[0]['id'];

                        // Update stored device ID
                        $playbackStateManager->setDeviceId($mix, $deviceId);
                        Log::info("Updated device ID to {$deviceId} for mix {$mix->id} after activation failure");

                        // Try to activate again
                        $spotifyService->activateDevice($user, $deviceId);
                    }
                }

                // Add a small delay to allow the device to be ready
                usleep(100000); // 100ms
            }

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
