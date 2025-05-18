<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Models\QueueSong;
use App\Services\SongPlaybackService;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\SpotifyService;
use App\Services\PlaybackStateManager;

class PlayPreviousSongController extends Controller
{
    public function __invoke(
        Mix $mix,
        SongPlaybackService $songPlaybackService,
        SpotifyService $spotifyService,
        PlaybackStateManager $playbackStateManager
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);

        // Get the current song that's playing
        $currentSong = $songPlaybackService->getCurrentlyPlayingSong($mix);
        if (!$currentSong) {
            return response()->json([
                'success' => false,
                'message' => 'No song is currently playing'
            ]);
        }

        // IMPROVED APPROACH: Track song navigation history in cache
        $historyKey = "mix:{$mix->id}:song_history";
        $songHistory = Cache::get($historyKey, []);

        // Check if we have history
        if (!empty($songHistory)) {
            // Get the last played song ID from history
            $previousSongId = array_pop($songHistory);

            // Store updated history back in cache
            Cache::put($historyKey, $songHistory, now()->addHours(1));

            // Get the previous song
            $previousSong = QueueSong::where('mix_id', $mix->id)
                ->where('id', $previousSongId)
                ->with('song')
                ->first();

            if ($previousSong) {
                Log::info("Found previous song {$previousSong->id} from history cache for mix {$mix->id}");
            }
        }

        // If no valid previous song found from history, return an error
        // This prevents unexpected behavior when no true "previous" song exists
        if (empty($previousSong)) {
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
