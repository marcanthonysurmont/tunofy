<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Models\QueueSong;
use App\Services\Spotify\SpotifyService;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\Playback\PlaybackStateManager;
use App\Events\DeviceUpdatedEvent;

class TransferPlaybackController extends Controller
{
    public function __invoke(Mix $mix, Request $request, SpotifyService $spotifyService, PlaybackStateManager $playbackStateManager): JsonResponse
    {
        $this->authorize('controlPlayback', $mix);

        $deviceId = $request->input('deviceId');

        if (!$deviceId) {
            return response()->json(['error' => 'Device ID is required'], 400);
        }

        Log::info("Transferring playback for mix {$mix->id} to device {$deviceId}");

        // Get current song and progress to preserve position
        $currentQueueSong = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'playing')
            ->with('song')
            ->first();

        if (!$currentQueueSong) {
            return response()->json(['error' => 'No song is currently playing'], 404);
        }

        // Get current progress to preserve position
        $playbackData = $spotifyService->getCurrentPlayback(Auth::user());
        $progressMs = $playbackData['progress_ms'] ?? 0;

        // FIRST call Spotify API - Activate the device and transfer playback
        try {
            $activationSuccess = $spotifyService->activateDevice(Auth::user(), $deviceId);

            if (!$activationSuccess) {
                return response()->json(['error' => 'Failed to activate device'], 500);
            }

            // Give Spotify a moment to register the device activation
            usleep(200000); // 200ms

            // Play the current song at the current position
            $playSuccess = $spotifyService->playTrackOnDevice(
                Auth::user(),
                $currentQueueSong->song->spotify_id,
                $deviceId,
                $progressMs
            );

            // ONLY AFTER API success, update state and broadcast
            if ($playSuccess) {
                // Store the new device ID
                $playbackStateManager->setDeviceId($mix, $deviceId);

                // Set device change grace period
                $playbackStateManager->setDeviceChanged($mix);

                // Broadcast device update
                event(new DeviceUpdatedEvent($mix));

                // Get updated playback data
                $updatedPlaybackData = $spotifyService->getCurrentPlayback(Auth::user());

                // If we got data, broadcast it
                if ($updatedPlaybackData) {
                    $playbackStateManager->setPlaybackData($mix, $updatedPlaybackData);
                    event(new PlaybackDataUpdatedEvent($mix, $updatedPlaybackData));
                }

                return response()->json([
                    'success' => true,
                    'device_id' => $deviceId
                ]);
            } else {
                return response()->json(['error' => 'Failed to play track on new device'], 500);
            }
        } catch (\Exception $e) {
            Log::error("Error transferring playback: " . $e->getMessage());
            return response()->json(['error' => 'Error transferring playback: ' . $e->getMessage()], 500);
        }
    }
}
