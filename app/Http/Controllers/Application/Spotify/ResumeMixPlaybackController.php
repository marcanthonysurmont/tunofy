<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use App\Models\Mix;
use App\Models\QueueSong;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\PlaybackStateManager;

class ResumeMixPlaybackController extends Controller
{
    public function __invoke(Mix $mix, SpotifyService $spotifyService, Request $request): JsonResponse
    {
        $this->authorize('controlPlayback', $mix);

        // RACE CONDITION CHECK: Only allow one command per second per mix
        $lastCommandKey = "mix:{$mix->id}:last_command";
        $lastCommandTime = Cache::get($lastCommandKey, 0);
        $now = microtime(true);

        // If less than 500ms has passed since last command, throttle
        if ($now - $lastCommandTime < 0.5) {
            Log::info("Throttling resume command - too soon after previous command");
            return response()->json([
                'success' => true,
                'is_playing' => true,
                'action' => 'resume',
                'throttled' => true
            ]);
        }

        // Set last command time
        Cache::put($lastCommandKey, $now, now()->addMinutes(5));

        // Get device_id from request if provided
        $deviceId = $request->input('device_id');

        // If device ID was provided, store it in cache
        if ($deviceId) {
            $playbackState = app(PlaybackStateManager::class);
            $playbackState->setDeviceId($mix, $deviceId);
            Log::info("Using device ID {$deviceId} to resume playback for mix {$mix->id}");
        } else {
            // Get stored device ID if none provided
            $playbackState = app(PlaybackStateManager::class);
            $deviceId = $playbackState->getDeviceId($mix);
        }

        // Get playback data through PlaybackStateManager
        $playbackData = $playbackState->getPlaybackData($mix) ?? [];

        // Ensure we have the minimum required fields
        if (!isset($playbackData['item'])) {
            // Get current playing song to populate missing data
            $currentSong = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'playing')
                ->with('song')
                ->first();

            if ($currentSong) {
                $playbackData['item'] = [
                    'id' => $currentSong->song->spotify_id,
                    'name' => $currentSong->song->name,
                    'duration_ms' => $currentSong->song->duration_ms,
                    'artists' => [['name' => $currentSong->song->artist]],
                    'album' => [
                        'images' => [['url' => $currentSong->song->image_url]]
                    ]
                ];
            }
        }

        // Set playing state and timestamp but DON'T broadcast yet
        $playbackData['is_playing'] = true;
        $playbackData['_timestamp'] = now()->timestamp;
        $playbackData['_action'] = 'resume';

        // FIRST call Spotify API
        try {
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;
            $success = false;

            // Get the current playing song from the queue
            $currentSong = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'playing')
                ->with('song')
                ->first();

            // Get the position from PlaybackStateManager
            $positionMs = $playbackState->getPausedPosition($mix);

            if ($positionMs > 0) {
                Log::info("Resuming playback at saved position {$positionMs}ms for mix {$mix->id}");
            } else {
                // Fallback to playback data
                $cachedPlaybackData = $playbackState->getPlaybackData($mix);
                if ($cachedPlaybackData && isset($cachedPlaybackData['progress_ms'])) {
                    $positionMs = $cachedPlaybackData['progress_ms'];
                    Log::info("Resuming playback at position from playback data: {$positionMs}ms for mix {$mix->id}");
                } else {
                    Log::info("No position data found, resuming from start for mix {$mix->id}");
                }
            }

            if ($currentSong) {
                // IMPORTANT: Use playTrackOnDevice WITH position_ms parameter
                $success = $spotifyService->playTrackOnDevice(
                    $user,
                    $currentSong->song->spotify_id,
                    $deviceId,
                    $positionMs  // Pass the previously saved position
                );

                // Set a device change grace period
                Cache::put("mix:{$mix->id}:device_changed", true, now()->addSeconds(5));
            } else {
                // Only try a generic resume if we don't have a specific track
                $success = $spotifyService->resumePlayback($user, $deviceId);
            }

            // ONLY AFTER success, update cache and broadcast
            if ($success) {
                // Remove paused flag
                $playbackState->setPaused($mix, false);
                $playbackState->setManualChange($mix);

                // Update cache and broadcast AFTER API success
                $playbackState->setPlaybackData($mix, $playbackData);
                event(new PlaybackDataUpdatedEvent($mix, $playbackData));

                return response()->json([
                    'success' => true,
                    'is_playing' => true
                ]);
            } else {
                Log::error("Failed to resume playback on Spotify for mix {$mix->id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to resume playback on Spotify'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error resuming playback: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume playback: ' . $e->getMessage()
            ]);
        }
    }
}
