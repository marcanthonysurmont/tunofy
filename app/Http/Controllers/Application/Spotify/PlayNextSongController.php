<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\SongPlaybackService;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\MixStatusChangedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\PlaybackStateManager;

class PlayNextSongController extends Controller
{
    public function __invoke(
        Mix $mix,
        SongPlaybackService $songPlaybackService,
        PlaybackStateManager $playbackStateManager
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);

        // Get device ID from PlaybackStateManager
        $deviceId = $playbackStateManager->getDeviceId($mix);

        if ($deviceId) {
            Log::info("PlayNextSongController using device ID {$deviceId} for mix {$mix->id}");
        }

        // Clear previous skip flags before creating a new one
        $playbackStateManager->forget($mix, 'manual_change');
        $playbackStateManager->setManualChange($mix);

        // Get the result from advancing to the next song
        // We should modify SongPlaybackService to accept a deviceId parameter
        $result = $songPlaybackService->advanceToNextSong($mix->id, $deviceId);

        // Ensure the manual change flag is still set after advancing
        $playbackStateManager->setManualChange($mix);

        // IMPORTANT: Check if this was the last song
        if (!$result['success'] && isset($result['queue_completed']) && $result['queue_completed']) {
            Log::info("Queue completed after skipping last song for mix {$mix->id}");

            // Set the mix to inactive
            $mix->update(['is_active' => false]);

            // Broadcast the queue completion AND deactivation
            event(new MixStatusChangedEvent(
                $mix,
                false,  // Set to false to indicate mix is now inactive
                'queue_completed'
            ));

            // Set cache flag to prevent further polling
            Cache::put("mix:{$mix->id}:queue_completed", true, now()->addHours(1));

            // Return the queue completion status
            return response()->json([
                'success' => false,
                'queue_completed' => true,
                'message' => 'Queue completed'
            ]);
        }

        if ($result['success']) {
            // Update playback data in cache with the new track
            $cacheKey = "mix:playback:" . $mix->id;
            $playbackData = [
                'is_playing' => true,
                'item' => [
                    'id' => $result['song']['spotify_id'],
                    'name' => $result['song']['name'],
                    'duration_ms' => $result['song']['duration_ms'],
                    'artists' => [['name' => $result['song']['artist']]],
                    'album' => [
                        'images' => [['url' => $result['song']['image_url']]]
                    ]
                ],
                '_timestamp' => now()->timestamp
            ];

            // Update the cache with the new track data BEFORE broadcasting
            Cache::put($cacheKey, $playbackData);

            // Debug log to see what's being sent
            Log::info("Broadcasting manual skip for mix {$mix->id} " . json_encode($playbackData));

            // Then broadcast the event with this updated data
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));

            // Flag as manual change to prevent immediate polling
            $playbackStateManager->setManualChange($mix);
        }

        return response()->json($result);
    }
}
