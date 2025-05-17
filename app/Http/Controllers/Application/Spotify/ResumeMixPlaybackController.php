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
        Cache::put("mix:playback:" . $mix->id, $playbackData);

        // Update playback data through PlaybackStateManager
        $playbackState->setPlaybackData($mix, $playbackData);

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
                    // Before playing the song, set a device change grace period
                    Cache::put("mix:{$mix->id}:device_changed", true, now()->addSeconds(5));

                    // If we have a song that should be playing, play it specifically
                    Log::info("Playing specific track {$currentSong->song->spotify_id} for mix {$mix->id}");
                    $spotifyService->playSong(Auth::user(), $currentSong->song->spotify_id, $deviceId);

                    // Make sure this is still marked as playing
                    if ($currentSong->status !== 'playing') {
                        $currentSong->update(['status' => 'playing']);

                        // If any other song is incorrectly marked as playing, fix it
                        QueueSong::where('mix_id', $mix->id)
                            ->where('status', 'playing')
                            ->where('id', '!=', $currentSong->id)
                            ->update(['status' => 'pending']);
                    }
                } else {
                    // First check if we're resuming after a user switch
                    $playbackState = app(PlaybackStateManager::class);
                    $switchSongId = $playbackState->get($mix, 'user_switch_song_id');

                    if ($switchSongId) {
                        // Clear the switch song ID after using it
                        $playbackState->forget($mix, 'user_switch_song_id');

                        // Find the song that was playing before the switch
                        $switchSong = QueueSong::find($switchSongId);

                        if ($switchSong) {
                            // Set this as the current song
                            $switchSong->update(['status' => 'playing']);

                            // Play this specific song
                            Log::info("Resuming song {$switchSong->song->spotify_id} that was playing before user switch");
                            $spotifyService->playSong(Auth::user(), $switchSong->song->spotify_id, $deviceId);

                            // Set device change grace period
                            Cache::put("mix:{$mix->id}:device_changed", true, now()->addSeconds(5));

                            // We handled the switch, so we're done
                            return response()->json([
                                'success' => true,
                                'is_playing' => true
                            ]);
                        }
                    }

                    // If we get here, there was no switch song or we couldn't find it
                    // Continue with the current logic to find the next pending song
                    $nextSong = QueueSong::where('mix_id', $mix->id)
                        ->where('status', 'pending')
                        ->orderBy('order')
                        ->with('song')
                        ->first();

                    if ($nextSong) {
                        // Before playing the song, set a device change grace period
                        Cache::put("mix:{$mix->id}:device_changed", true, now()->addSeconds(5));

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
