<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\SongPlaybackService;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PlayPreviousSongController extends Controller
{
    public function __invoke(Mix $mix, SongPlaybackService $songPlaybackService): JsonResponse
    {
        $this->authorize('update', $mix);

        $result = $songPlaybackService->returnToPreviousSong($mix->id);

        if ($result) {
            // Ensure identical data structure
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

            // Debug log to see what's being sent
            Log::info("Broadcasting manual previous for mix {$mix->id}", ['data' => $playbackData]);

            event(new PlaybackDataUpdatedEvent($mix, $playbackData));

            Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));
        }

        return response()->json($result);
    }
}
