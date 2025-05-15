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

class ResumeMixPlaybackController extends Controller
{
    public function __invoke(Mix $mix, SpotifyService $spotifyService, Request $request): JsonResponse
    {
        $this->authorize('controlPlayback', $mix);

        // Get device_id from request if provided
        $deviceId = $request->input('device_id');

        // If device ID was provided, store it in cache
        if ($deviceId) {
            Cache::put("mix:{$mix->id}:device_id", $deviceId, now()->addHours(12));
            Log::info("Using device ID {$deviceId} to resume playback for mix {$mix->id}");
        }

        // Check if this was a regular user pause (not a co-DJ change pause)
        $wasUserPaused = Cache::has("mix:{$mix->id}:user_paused");

        // If it was a regular pause, use resumePlayback instead of trying to play a specific track
        if ($wasUserPaused) {
            Cache::forget("mix:{$mix->id}:user_paused");

            // Just resume whatever was playing
            $resumeResult = $spotifyService->resumePlayback(Auth::user(), $deviceId);
            if (!$resumeResult) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to resume playback'
                ], 500);
            }

            // Skip to the rest of the code that handles the cache and events
            goto resume_playback_complete;
        }

        // Get the current playing song from the queue
        $currentSong = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'playing')
            ->first();

        if ($currentSong) {
            // If we have a song that should be playing, play it specifically
            Log::info("Playing specific track {$currentSong->song->spotify_id} for mix {$mix->id}");

            $spotifyUri = "spotify:track:{$currentSong->song->spotify_id}";
            $playResult = $spotifyService->playSong(Auth::user(), $spotifyUri, $deviceId);

            if (!$playResult) {
                Log::error("Failed to play specific track");
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to play track'
                ], 500);
            }
        } else {
            // If no song is currently playing, try to get the next song in queue
            $nextSong = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'pending')
                ->orderBy('order') // Using 'order' column instead
                ->first();

            if ($nextSong) {
                // Update status to playing
                $nextSong->update(['status' => 'playing']);

                // Play this song
                Log::info("Playing next track {$nextSong->song->spotify_id} for mix {$mix->id}");

                $spotifyUri = "spotify:track:{$nextSong->song->spotify_id}";
                $playResult = $spotifyService->playSong(Auth::user(), $spotifyUri, $deviceId);

                if (!$playResult) {
                    Log::error("Failed to play next track");
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to play next track'
                    ], 500);
                }
            } else {
                // No songs in queue, just resume whatever was playing
                $resumeResult = $spotifyService->resumePlayback(Auth::user(), $deviceId);
                if (!$resumeResult) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to resume playback'
                    ], 500);
                }
            }
        }

        // Clear any device failure flags on successful playback
        Cache::forget("mix:{$mix->id}:device_failure");

        // Add a label at the end of your method before the return:
        resume_playback_complete:

        // Remove paused flag
        Cache::forget("mix:{$mix->id}:paused");

        // Update playback data in cache and broadcast
        $cacheKey = "mix:playback:" . $mix->id;
        $playbackData = Cache::get($cacheKey, []);
        $playbackData['is_playing'] = true;
        $playbackData['_timestamp'] = now()->timestamp;

        Log::info("Broadcasting resume event for mix {$mix->id}");
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        Cache::put($cacheKey, $playbackData);
        Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));

        Log::info("Playback resumed for mix {$mix->id}");

        return response()->json([
            'success' => true,
            'is_playing' => true
        ]);
    }
}
