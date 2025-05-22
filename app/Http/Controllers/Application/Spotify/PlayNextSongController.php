<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Events\QueueStateUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\Playback\SongPlaybackService;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\MixStatusChangedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Spotify\SpotifyService;
use App\Services\Playback\PlaybackStateManager;
use App\Models\QueueSong;
use Illuminate\Support\Facades\Auth;

class PlayNextSongController extends Controller
{
    public function __invoke(
        Mix $mix,
        SongPlaybackService $songPlaybackService,
        SpotifyService $spotifyService,
        PlaybackStateManager $playbackStateManager
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);

        // RACE CONDITION CHECK: Only allow one command per 300ms per mix
        $lastCommandKey = "mix:{$mix->id}:last_command";
        $lastCommandTime = Cache::get($lastCommandKey, 0);
        $now = microtime(true);

        // If less than 300ms has passed since last command, throttle
        if ($now - $lastCommandTime < 0.3) {
            Log::info("Throttling next song command - too soon after previous command");

            // OPTIMIZATION: Return the last successful response to make UI feel more responsive
            $lastResponseKey = "mix:{$mix->id}:last_next_response";
            $lastResponse = Cache::get($lastResponseKey);
            if ($lastResponse) {
                return response()->json(array_merge(
                    $lastResponse,
                    ['throttled' => true]
                ));
            }

            return response()->json([
                'success' => true,
                'throttled' => true,
                'message' => 'Command throttled'
            ]);
        }

        // Set last command time
        Cache::put($lastCommandKey, $now, now()->addMinutes(5));

        // 1. Get the next song data and mark current song as finished
        $nextSong = $songPlaybackService->getNextSongToPlay($mix->id);
        $currentSong = $songPlaybackService->getCurrentlyPlayingSong($mix);

        // Track song history for proper "previous song" navigation
        if ($currentSong) {
            // Let PlaybackStateManager handle the song history - ALWAYS add to history when skipping forward
            $playbackStateManager->addToSongHistory($mix, $currentSong->id);

            // Now update song status
            $currentSong->update(['status' => 'finished', 'played_at' => now()]);
        }

        // IMPORTANT: Check and extend queue here - BEFORE getting next song again
        // This ensures we always have songs ready
        $pendingSongsCount = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingSongsCount <= 5) {
            Log::info("Queue running low during skip, extending queue for mix {$mix->id}");
            $songPlaybackService->extendQueueIfNeeded($mix);

            // Re-fetch next song since queue might have changed
            $nextSong = $songPlaybackService->getNextSongToPlay($mix->id);
        }

        // Check if queue is completed
        if (!$nextSong) {
            Log::info("Queue completed for mix {$mix->id}");

            $spotifyService->pausePlayback(Auth::user());
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

        // 2. Prepare the playback data but DON'T broadcast yet
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
            '_action' => 'next'
        ];

        // 3. Faster API calls to Spotify
        $deviceId = $playbackStateManager->getDeviceId($mix);
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        $success = false;
        if ($deviceId) {
            // OPTIMIZATION: Don't set device_changed grace period for every command
            // Only set it if the device was explicitly changed

            // OPTIMIZATION: Skip device activation if it was recently activated
            $recentlyActivated = $playbackStateManager->isDeviceRecentlyActivated($mix);
            $activationSuccess = $recentlyActivated;

            if (!$recentlyActivated) {
                $activationSuccess = $spotifyService->activateDevice($user, $deviceId);
                if ($activationSuccess) {
                    // Cache activation status for 30 seconds to avoid repeated calls
                    $playbackStateManager->setDeviceActivated($mix, true, 30);
                }
            }

            if ($activationSuccess) {
                // OPTIMIZATION: No sleep/delay, immediate play command
                $success = $spotifyService->playTrackOnDevice(
                    $user,
                    $nextSong->song->spotify_id,
                    $deviceId
                );
            }
        }

        // 4. Faster response after Spotify API call
        if ($success) {
            // Update status and cache in parallel (not sequential)
            $nextSong->update(['status' => 'playing', 'played_at' => now()]);
            $playbackStateManager->setManualChange($mix);
            $playbackStateManager->setPlaybackData($mix, $playbackData);

            // Broadcast event
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));

            // Cache the last successful response
            $response = [
                'success' => true,
                'song' => $nextSong->song
            ];

            Cache::put("mix:{$mix->id}:last_next_response", $response, now()->addMinutes(1));

            QueueStateUpdatedEvent::dispatch($mix);

            return response()->json($response);
        } else {
            // Simplified error response
            return response()->json([
                'success' => false
            ]);
        }
    }
}
