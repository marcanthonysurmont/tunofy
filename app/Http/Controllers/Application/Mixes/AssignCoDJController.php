<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Events\CoDJUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignCoDJRequest;
use App\Models\Mix;
use App\Services\SongPlaybackService;
use App\Services\SpotifyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;

class AssignCoDJController extends Controller
{
    public function __invoke(AssignCoDJRequest $request, Mix $mix, SpotifyService $spotifyService, SongPlaybackService $songPlaybackService): RedirectResponse
    {
        $this->authorize('assignCoDJ', $mix);

        $validated = $request->validated();

        try {
            $mix->update(['co_dj_id' => $validated['user_id']]);

            $songPlaybackService->prepareQueueForUserSwitch($mix->id);

            CoDJUpdatedEvent::dispatch($mix->coDJ);

            Cache::forget("mix:{$mix->id}:device_id");
            // Check if already paused to avoid unnecessary API calls
            $alreadyPaused = Cache::has("mix:{$mix->id}:paused");

            // Only call the Spotify API if not already paused
            if (!$alreadyPaused) {
                $pauseResult = $spotifyService->pausePlayback(Auth::user());
                if (!$pauseResult) {
                    return response()->json(['error' => 'Failed to pause playback'], 500);
                }
            }

            // Get FRESH playback data instead of using potentially stale cache
            $freshPlaybackData = $spotifyService->getCurrentPlayback(Auth::user());
            
            // Use fresh data or fall back to cached if fresh is unavailable
            $cacheKey = "mix:playback:" . $mix->id;
            $playbackData = $freshPlaybackData ?: Cache::get($cacheKey, []);
            
            // Set minimum required fields for a pause event
            $playbackData['is_playing'] = false;
            $playbackData['_timestamp'] = now()->timestamp;

            // Broadcast the pause event with fresh data
            Log::info("Broadcasting pause event for mix {$mix->id}");
            event(new PlaybackDataUpdatedEvent($mix, $playbackData));

            // Update cache with the fresh data 
            Cache::put($cacheKey, $playbackData);
            Cache::put("mix:{$mix->id}:paused", true);
            Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));

            Log::info("Playback paused for mix {$mix->id}");

            return redirect()->back()
                ->with('success', 'Co-DJ assigned successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('success', 'Co-DJ assigned successfully.');
        }
    }
}