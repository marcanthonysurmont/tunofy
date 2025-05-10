<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Models\QueueSong;
use App\Services\SpotifyService;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TransferPlaybackController extends Controller
{
    public function __invoke(Mix $mix, Request $request, SpotifyService $spotifyService): JsonResponse
    {
        $this->authorize('controlPlayback', $mix);

        $deviceId = $request->input('deviceId');

        if (!$deviceId) {
            return response()->json(['error' => 'Device ID is required'], 400);
        }

        // Store the new device ID in cache
        Cache::put("mix:{$mix->id}:device_id", $deviceId, now()->addDay());

        // Set a flag to prevent track mismatch detection during device change
        Cache::put("mix:{$mix->id}:device_changed", true, now()->addSeconds(10));

        Log::info("Transferring playback for mix {$mix->id} to device {$deviceId}");

        // Get the current song
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

        // Activate the device and transfer playback
        $activationSuccess = $spotifyService->activateDevice(Auth::user(), $deviceId);

        if (!$activationSuccess) {
            return response()->json(['error' => 'Failed to activate device'], 500);
        }

        // Play the current song on the new device, preserving position
        $playResult = $spotifyService->playTrackOnDevice(
            Auth::user(),
            $currentQueueSong->song->spotify_id,
            $deviceId,
            $progressMs
        );

        if (!$playResult) {
            return response()->json(['error' => 'Failed to transfer playback'], 500);
        }

        // Get fresh playback data after transfer
        sleep(1); // Brief delay to ensure API has updated
        $freshPlaybackData = $spotifyService->getCurrentPlayback(Auth::user());

        if ($freshPlaybackData) {
            // Update cache with new playback data
            $cacheKey = "mix:playback:" . $mix->id;
            $freshPlaybackData['_timestamp'] = now()->timestamp;
            Cache::put($cacheKey, $freshPlaybackData);

            // Broadcast the updated playback data
            event(new PlaybackDataUpdatedEvent($mix, $freshPlaybackData));
        }

        Log::info("Successfully transferred playback to device {$deviceId} for mix {$mix->id}");

        return response()->json([
            'success' => true,
            'message' => 'Playback transferred successfully',
            'device_id' => $deviceId
        ]);
    }
}
