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

        // RACE CONDITION CHECK: Only allow one command per second per mix
        $lastCommandKey = "mix:{$mix->id}:last_command";
        $lastCommandTime = Cache::get($lastCommandKey, 0);
        $now = microtime(true);

        // If less than 500ms has passed since last command, throttle
        if ($now - $lastCommandTime < 0.5) {
            Log::info("Throttling next song command - too soon after previous command");
            return response()->json([
                'success' => true,
                'throttled' => true,
                'message' => 'Command throttled to prevent race conditions'
            ]);
        }

        // Set last command time
        Cache::put($lastCommandKey, $now, now()->addMinutes(5));

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

        // 3. Update cache and broadcast IMMEDIATELY (before Spotify API call)
        $playbackData = [
            'is_playing' => true,
            'progress_ms' => 0,
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
            '_action' => 'next' // Add action type for frontend
        ];

        // Update via PlaybackStateManager (consistent state)
        $playbackStateManager->setPlaybackData($mix, $playbackData);

        // Broadcast immediately so UI updates right away
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        // 4. THEN handle the Spotify API call (potentially slow)
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
