<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use App\Models\Mix;
use App\Models\QueueSong;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Update playback data in cache and broadcast IMMEDIATELY
        $cacheKey = "mix:playback:" . $mix->id;
        $playbackData = Cache::get($cacheKey, []);

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

        // Set playing state, timestamp and action
        $playbackData['is_playing'] = true;
        $playbackData['_timestamp'] = now()->timestamp;
        $playbackData['_action'] = 'resume';

        // Remove paused flag immediately
        $playbackState->setPaused($mix, false);
        $playbackState->setManualChange($mix);

        // Broadcast the resume event BEFORE Spotify API call
        Log::info("Broadcasting resume event for mix {$mix->id}");
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));
        Cache::put($cacheKey, $playbackData);

        // Check if this was a regular user pause (not a co-DJ change pause)
        $wasUserPaused = Cache::has("mix:{$mix->id}:user_paused");

        try {
            // AFTER UI update, handle the Spotify playback (possibly slow operation)
            if ($wasUserPaused) {
                Cache::forget("mix:{$mix->id}:user_paused");

                // Just resume what was playing before
                $spotifyService->resumePlayback(Auth::user(), $deviceId);
            } else {
                // Get the current playing song from the queue
                $currentSong = QueueSong::where('mix_id', $mix->id)
                    ->where('status', 'playing')
                    ->with('song')
                    ->first();

                if ($currentSong) {
                    // If we have a song that should be playing, play it specifically
                    Log::info("Playing specific track {$currentSong->song->spotify_id} for mix {$mix->id}");
                    $spotifyService->playSong(Auth::user(), $currentSong->song->spotify_id, $deviceId);
                } else {
                    // If no song is currently playing, try to get the next song in queue
                    $nextSong = QueueSong::where('mix_id', $mix->id)
                        ->where('status', 'pending')
                        ->orderBy('order')
                        ->with('song')
                        ->first();

                    if ($nextSong) {
                        // Update status to playing
                        $nextSong->update(['status' => 'playing']);

                        // Play this song
                        Log::info("Playing next track {$nextSong->song->spotify_id} for mix {$mix->id}");
                        $spotifyService->playSong(Auth::user(), $nextSong->song->spotify_id, $deviceId);
                    } else {
                        // No songs in queue, just resume whatever was playing
                        $spotifyService->resumePlayback(Auth::user(), $deviceId);
                    }
                }
            }

            // Clear any device failure flags on successful playback
            Cache::forget("mix:{$mix->id}:device_failure");

            Log::info("Playback resumed for mix {$mix->id}");

            return response()->json([
                'success' => true,
                'is_playing' => true
            ]);
        } catch (\Exception $e) {
            Log::error("Error resuming playback: " . $e->getMessage());

            // Even if Spotify API calls fail, we've already updated the UI
            return response()->json([
                'success' => true,
                'is_playing' => true
            ]);
        }
    }
}
