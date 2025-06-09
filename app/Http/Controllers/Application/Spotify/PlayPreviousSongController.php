<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Models\QueueSong;
use App\Services\Playback\SongPlaybackService;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Spotify\SpotifyService;
use App\Services\Playback\PlaybackStateManager;
use App\Events\QueueStateUpdatedEvent;
use App\Models\MixStat;
use Illuminate\Support\Facades\DB;
use App\Events\StatUpdatedEvent;

class PlayPreviousSongController extends Controller
{
    public function __invoke(
        Mix $mix,
        SpotifyService $spotifyService,
        PlaybackStateManager $playbackStateManager
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);

        // Get the current song that's playing
        $currentSong = QueueSong::currentlyPlayingForMix($mix);
        if (!$currentSong) {
            return response()->json([
                'success' => false,
                'message' => 'No song is currently playing'
            ]);
        }

        // Get song history from PlaybackStateManager
        $previousSongId = $playbackStateManager->getPreviousSongFromHistory($mix);

        // Check if we have a previous song
        if ($previousSongId) {
            // Get the previous song
            $previousSong = QueueSong::where('mix_id', $mix->id)
                ->where('id', $previousSongId)
                ->with('song')
                ->first();

            if ($previousSong) {
                Log::info("Found previous song {$previousSong->id} from history cache for mix {$mix->id}");
            }
        } else {
            Log::info("No previous song found in history for mix {$mix->id} - rejecting previous command");
            return response()->json([
                'success' => false,
                'message' => 'No previous song available'
            ]);
        }

        // 3. Update database status (fast operations)
        $currentSong->update(['status' => 'pending']);
        $previousSong->update(['status' => 'playing']);

        // Prepare the playback data but DON'T broadcast yet
        $playbackData = [
            'is_playing' => true,
            'item' => [
                'id' => $previousSong->song->spotify_id,
                'name' => $previousSong->song->name,
                'duration_ms' => $previousSong->song->duration_ms,
                'artists' => [['name' => $previousSong->song->artist]],
                'album' => [
                    'images' => [['url' => $previousSong->song->image_url]]
                ]
            ],
            '_timestamp' => now()->timestamp,
            '_action' => 'previous'
        ];

        // OPTIMIZATION: Reuse device activation status from cache
        $deviceId = $playbackStateManager->getDeviceId($mix);
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;
        $recentlyActivated = Cache::get("mix:{$mix->id}:device_activated", false);
        $oldPlaybackData = $spotifyService->getCurrentPlayback($user);

        MixStat::updateOrCreate(
            ['mix_id' => $mix->id],
            [
                'songs_played' => DB::raw('songs_played + 1'),
                'minutes_played' => DB::raw('minutes_played + ' . $oldPlaybackData['progress_ms'] / 60000),
            ]
        );

        StatUpdatedEvent::dispatch($mix);

        if ($deviceId) {
            // Only activate if not recently activated
            $activationSuccess = $recentlyActivated || $spotifyService->activateDevice($user, $deviceId);

            if ($activationSuccess && !$recentlyActivated) {
                Cache::put("mix:{$mix->id}:device_activated", true, now()->addSeconds(30));
            }

            if ($activationSuccess) {
                // Play song immediately
                $success = $spotifyService->playTrackOnDevice(
                    $user,
                    $previousSong->song->spotify_id,
                    $deviceId
                );
            }
        }

        // Update DB and broadcast in parallel for speed
        if (isset($success) && $success) {
            // Set manual change flag
            $playbackStateManager->setManualChange($mix);

            // Update cache
            $playbackStateManager->setPlaybackData($mix, $playbackData);

            // Broadcast AFTER successful API call
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));

            QueueStateUpdatedEvent::dispatch($mix);

            return response()->json([
                'success' => true,
                'song' => [
                    'id' => $previousSong->song->id,
                    'spotify_id' => $previousSong->song->spotify_id,
                    'name' => $previousSong->song->name,
                    'artist' => $previousSong->song->artist,
                    'duration_ms' => $previousSong->song->duration_ms,
                    'image_url' => $previousSong->song->image_url
                ]
            ]);
        } else {
            // Revert database changes
            $currentSong->update(['status' => 'playing']);
            $previousSong->update(['status' => 'finished']);

            Log::error("Failed to play previous song on Spotify for mix {$mix->id}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to play previous song on Spotify'
            ]);
        }
    }
}
