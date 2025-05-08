<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Services\SongPlaybackService;
use App\Services\SpotifyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\CoDJUpdatedEvent;

class RemoveCoDJController extends Controller
{
    public function __invoke(Mix $mix, SpotifyService $spotifyService, SongPlaybackService $songPlaybackService): RedirectResponse
    {
        $this->authorize('assignCoDJ', $mix);

        try {
            // Store the co-DJ user instance before we remove the relationship
            $coDJ = $mix->coDJ;

            // Determine the active user for playback (co-DJ if set, otherwise mix owner)
            $activeUser = $mix->co_dj_id ? $coDJ : Auth::user();

            // First, pause playback on the ACTIVE user's device
            $alreadyPaused = Cache::has("mix:{$mix->id}:paused");

            // Get the current playback data BEFORE pausing to preserve the current song
            $cacheKey = "mix:playback:" . $mix->id;
            $playbackData = Cache::get($cacheKey, []);

            // Store the current track information if available
            $currentTrack = null;
            if (!empty($playbackData['item'])) {
                $currentTrack = $playbackData['item'];
                Log::info("Stored current track {$currentTrack['id']} before co-DJ removal");
            }

            // Only call the Spotify API if not already paused
            if (!$alreadyPaused) {
                Log::info("Pausing Spotify playback for co-DJ (user {$activeUser->id}) before removal");
                $pauseResult = $spotifyService->pausePlayback($activeUser);

                if (!$pauseResult) {
                    Log::warning("Failed to pause playback for co-DJ user {$activeUser->id}, continuing with removal anyway");
                    // We'll continue even if pausing fails
                }
            }

            // Now that we've attempted to pause, remove the co-DJ relationship
            $mix->update(['co_dj_id' => null]);
            event(new CoDJUpdatedEvent($coDJ));

            // Prepare queue for switch back to the mix owner
            $songPlaybackService->prepareQueueForUserSwitch($mix->id);

            // Clear device from cache to prevent using a potentially unavailable device
            Cache::forget("mix:{$mix->id}:device_id");

            // Create new playback data with the current track info but paused state
            $freshPlaybackData = [
                'is_playing' => false,
                '_timestamp' => now()->timestamp
            ];

            // If we had track data, preserve it in the fresh data
            if ($currentTrack) {
                $freshPlaybackData['item'] = $currentTrack;
            }

            // IMPORTANT: Update the cache with the complete playback data including track info
            Cache::put($cacheKey, $freshPlaybackData);
            Cache::put("mix:{$mix->id}:paused", true);
            Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));

            // Broadcast the pause event with the complete playback data
            Log::info("Broadcasting pause event for mix {$mix->id}");
            event(new PlaybackDataUpdatedEvent($mix, $freshPlaybackData));

            Log::info("Playback paused for mix {$mix->id}");

            return redirect()->back()
                ->with('success', 'Co-DJ removed successfully.');
        } catch (\Exception $e) {
            Log::error("Error removing co-DJ: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error removing co-DJ: ' . $e->getMessage());
        }
    }
}
