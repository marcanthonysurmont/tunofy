<?php

namespace App\Services;

use App\Events\CoDJUpdatedEvent;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\MixStatusChangedEvent;
use App\Models\Mix;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CoDJManagementService
{
    public function __construct(protected SpotifyService $spotifyService, protected SongPlaybackService $songPlaybackService)
    {
    }

    public function assignCoDJ(Mix $mix, User $user): void
    {
        // Update the mix
        $mix->update(['co_dj_id' => $user->id]);

        // Prepare queue for user switch
        $this->songPlaybackService->prepareQueueForUserSwitch($mix->id);

        // Dispatch event for the new co-DJ
        CoDJUpdatedEvent::dispatch($user);

        // Clear device from cache
        Cache::forget("mix:{$mix->id}:device_id");

        // Handle playback state - use the mix owner when assigning a co-DJ
        $this->pauseAndUpdatePlaybackState($mix, $mix->user);
    }

    /**
     * Remove a co-DJ from a mix
     */
    public function removeCoDJ(Mix $mix): void
    {
        // Store co-DJ before removing relationship
        $coDJ = $mix->coDJ;

        // Update the mix
        $mix->update(['co_dj_id' => null]);

        // Notify the removed co-DJ
        if ($coDJ) {
            CoDJUpdatedEvent::dispatch($coDJ);

            // Add this line to broadcast the mix status change to all listeners
            event(new MixStatusChangedEvent($mix, false, 'co_dj_left'));
        }

        // Prepare queue for user switch
        $this->songPlaybackService->prepareQueueForUserSwitch($mix->id);

        // Clear device from cache
        Cache::forget("mix:{$mix->id}:device_id");

        // Handle playback state - use the co-DJ when removing a co-DJ
        $this->pauseAndUpdatePlaybackState($mix, $coDJ ?? $mix->user);
    }

    /**
     * Pause playback and update state
     */
    private function pauseAndUpdatePlaybackState(Mix $mix, User $userToPause): void
    {
        // Check if already paused
        $alreadyPaused = Cache::has("mix:{$mix->id}:paused");

        // Only pause if not already paused
        if (!$alreadyPaused) {
            $this->spotifyService->pausePlayback($userToPause);
        }

        // Get cached playback data
        $cacheKey = "mix:playback:" . $mix->id;
        $playbackData = Cache::get($cacheKey);

        // If no cached data at all, get fresh data as a fallback
        if (!$playbackData) {
            // Get fresh playback data as a fallback
            $freshPlaybackData = $this->spotifyService->getCurrentPlayback($userToPause);
            if ($freshPlaybackData) {
                $playbackData = $freshPlaybackData;
            } else {
                // If we still don't have playback data, create an empty structure
                $playbackData = ['is_playing' => false];
            }
        }

        // If we have playback info with a track, store it for resume
        if (isset($playbackData['item']['id'])) {
            // Store the currently playing track info for more accurate resume
            Cache::put("mix:{$mix->id}:current_track", [
                'uri' => "spotify:track:{$playbackData['item']['id']}",
                'position_ms' => $playbackData['progress_ms'] ?? 0,
            ], now()->addHours(1));
        }

        // Update playback data with pause state
        $playbackData['is_playing'] = false;
        $playbackData['_timestamp'] = now()->timestamp;

        // Broadcast pause event
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        // Update cache
        Cache::put($cacheKey, $playbackData);
        Cache::put("mix:{$mix->id}:paused", true);
        Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));
    }
}
