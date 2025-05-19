<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\Spotify\SpotifyService;
use App\Services\Playback\PlaybackStateManager;

class PauseMixPlaybackController extends Controller
{
    public function __invoke(
        Mix $mix,
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
            Log::info("Throttling pause command - too soon after previous command");
            return response()->json([
                'success' => true,
                'is_playing' => false,
                'action' => 'pause',
                'throttled' => true
            ]);
        }

        // Set last command time
        Cache::put($lastCommandKey, $now, now()->addMinutes(5));

        Log::info("Pausing Spotify playback for user " . Auth::id());

        // 1. Get current playback data through PlaybackStateManager
        $playbackData = $playbackStateManager->getPlaybackData($mix) ?? [];

        // 2. Prepare the updated playback data but DON'T broadcast yet
        $playbackData['is_playing'] = false;
        $playbackData['_timestamp'] = now()->timestamp;
        $playbackData['_action'] = 'pause';

        // 3. FIRST send command to Spotify API
        try {
            // Get the appropriate user
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

            // Get device ID if available
            $deviceId = $playbackStateManager->getDeviceId($mix);

            // Before calling Spotify API to pause, get the current position
            try {
                // Get current playback data with position
                $currentPlaybackData = $spotifyService->getCurrentPlayback($user);

                // IMPORTANT: Capture the current position before pausing
                if ($currentPlaybackData && isset($currentPlaybackData['progress_ms'])) {
                    $position = $currentPlaybackData['progress_ms'];
                    $playbackData['progress_ms'] = $position;

                    // Use PlaybackStateManager methods to store position
                    $playbackStateManager->setPausedPosition($mix, $position);

                    Log::info("Saving position {$position}ms before pausing mix {$mix->id}");
                }
            } catch (\Exception $e) {
                Log::error("Error fetching current playback position: " . $e->getMessage());
            }

            // Call Spotify API to pause
            $success = $spotifyService->pausePlayback($user, $deviceId);

            // 4. ONLY AFTER Spotify API success, update cache and broadcast
            if ($success) {
                // Set the paused state in PlaybackStateManager
                $playbackStateManager->setPaused($mix, true);
                $playbackStateManager->setManualChange($mix);

                // Use PlaybackStateManager methods to store user paused state
                $playbackStateManager->setUserPaused($mix, true);

                // Update playback data and broadcast AFTER API success
                $playbackStateManager->setPlaybackData($mix, $playbackData);
                event(new PlaybackDataUpdatedEvent($mix, $playbackData));

                return response()->json([
                    'success' => true,
                    'is_playing' => false,
                    'action' => 'pause'
                ]);
            } else {
                Log::error("Failed to pause playback on Spotify for mix {$mix->id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to pause playback on Spotify'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error pausing playback: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause playback: ' . $e->getMessage()
            ]);
        }
    }
}
