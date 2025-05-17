<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\SpotifyService;
use App\Services\PlaybackStateManager;

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

        // 2. First update the playback data with paused state
        $playbackData['is_playing'] = false;
        $playbackData['_timestamp'] = now()->timestamp;
        $playbackData['_action'] = 'pause';

        // 3. Update cache and broadcast IMMEDIATELY
        Cache::put("mix:playback:" . $mix->id, $playbackData);
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        // 4. Set the paused state in PlaybackStateManager
        $playbackStateManager->setPaused($mix, true);
        $playbackStateManager->setManualChange($mix);

        // 5. Store in cache that user manually paused
        Cache::put("mix:{$mix->id}:user_paused", true, now()->addMinutes(30));

        // 6. Update playback data through PlaybackStateManager
        $playbackStateManager->setPlaybackData($mix, $playbackData);

        // 7. Send pause command to Spotify AFTER broadcasting
        try {
            // Get the appropriate user
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

            // Get device ID if available
            $deviceId = $playbackStateManager->getDeviceId($mix);

            // Call Spotify API to pause
            $spotifyService->pausePlayback($user, $deviceId);

            return response()->json([
                'success' => true,
                'is_playing' => false,
                'action' => 'pause'
            ]);
        } catch (\Exception $e) {
            Log::error("Error pausing playback: " . $e->getMessage());

            // Even if Spotify pause fails, we've already updated the UI
            return response()->json([
                'success' => true,
                'is_playing' => false,
                'action' => 'pause'
            ]);
        }
    }
}
