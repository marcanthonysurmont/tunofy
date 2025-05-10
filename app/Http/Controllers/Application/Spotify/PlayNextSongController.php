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

class PlayNextSongController extends Controller
{
    public function __invoke(Mix $mix, SongPlaybackService $songPlaybackService): JsonResponse
    {
        $this->authorize('controlPlayback', $mix);

        $result = $songPlaybackService->advanceToNextSong($mix->id);

        // Check for queue completion
        if (isset($result['queue_completed']) && $result['queue_completed']) {
            // Broadcast a clear and explicit event for queue completion
            event(new MixStatusChangedEvent(
                $mix,
                true,  // Mix is still active
                'queue_completed'
            ));

            return response()->json([
                'success' => true,
                'message' => 'Queue completed',
                'queue_completed' => true
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
            Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));
        }

        return response()->json($result);
    }
}
