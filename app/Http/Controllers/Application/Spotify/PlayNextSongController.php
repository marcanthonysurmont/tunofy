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

class PlayNextSongController extends Controller
{
    public function __invoke(
        Request $request,
        Mix $mix,
        SongPlaybackService $songPlaybackService
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);

        // First, clear any previous skip flags to ensure fresh state
        Cache::forget("mix:{$mix->id}:manual_change");

        // Set the skip flag with a longer TTL to cover rapid skips
        Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));

        // Get the result from advancing to the next song
        $result = $songPlaybackService->advanceToNextSong($mix->id);

        // After skip, ensure the flag is still set to prevent polling right after
        Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));

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
            Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));
        }

        return response()->json($result);
    }
}
