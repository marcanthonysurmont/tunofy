<?php

namespace App\Services;

use App\Events\CoDJUpdatedEvent;
use App\Events\PlaybackDataUpdatedEvent;
use App\Models\Mix;
use App\Models\User;
use App\Models\QueueSong;
use Illuminate\Support\Facades\Cache;

class CoDJManagementService
{
    public function __construct(
        protected SpotifyService $spotifyService,
        protected SongPlaybackService $songPlaybackService,
        protected PlaybackStateManager $playbackStateManager
    ) {
    }

    public function assignCoDJ(Mix $mix, User $user): void
    {
        // Capture current playing track before switch
        $currentlyPlaying = QueueSong::where('mix_id', $mix->id)
                            ->where('status', 'playing')
                            ->with('song')
                            ->first();

        // Update the mix
        $mix->update(['co_dj_id' => $user->id]);

        // Prepare queue for user switch
        $this->songPlaybackService->prepareQueueForUserSwitch($mix);

        // If there was a playing song, ensure it's correctly set after transition
        if ($currentlyPlaying) {
            // REFACTORED: Use PlaybackStateManager instead of direct Cache call
            $this->playbackStateManager->set(
                $mix,
                'transition_track',
                $currentlyPlaying->song->spotify_id,
            );
        }

        // Dispatch event with more complete state
        CoDJUpdatedEvent::dispatch($user, [
            'playing_track' => $currentlyPlaying ? $currentlyPlaying->song->spotify_id : null,
        ]);

        // REFACTORED: Use PlaybackStateManager to clear device ID
        $this->playbackStateManager->forget($mix, 'device_id');

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

        // Capture current playing track before switch
        $currentlyPlaying = QueueSong::where('mix_id', $mix->id)
                            ->where('status', 'playing')
                            ->with('song')
                            ->first();

        // Update the mix
        $mix->update(['co_dj_id' => null]);

        // Prepare queue for user switch
        $this->songPlaybackService->prepareQueueForUserSwitch($mix);

        // Notify the removed co-DJ
        if ($coDJ) {
            CoDJUpdatedEvent::dispatch($coDJ, [
                'playing_track' => $currentlyPlaying ? $currentlyPlaying->song->spotify_id : null,
            ]);
        }

        // REFACTORED: Use PlaybackStateManager to clear device ID
        $this->playbackStateManager->forget($mix, 'device_id');

        // Handle playback state - use the co-DJ when removing a co-DJ
        $this->pauseAndUpdatePlaybackState($mix, $coDJ ?? $mix->user);
    }

    /**
     * Pause playback and update state
     */
    private function pauseAndUpdatePlaybackState(Mix $mix, User $userToPause): void
    {
        // REFACTORED: Check if already paused using PlaybackStateManager
        $alreadyPaused = $this->playbackStateManager->isPaused($mix);

        // Only pause if not already paused
        if (!$alreadyPaused) {
            $this->spotifyService->pausePlayback($userToPause);
        }

        // Get cached playback data
        $playbackData = $this->playbackStateManager->getPlaybackData($mix);

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
            // ALREADY REFACTORED: Using PlaybackStateManager's set method
            $this->playbackStateManager->set($mix, 'current_track', [
                'uri' => "spotify:track:{$playbackData['item']['id']}",
                'position_ms' => $playbackData['progress_ms'] ?? 0,
            ]);
        }

        // Update playback data with pause state
        $playbackData['is_playing'] = false;
        $playbackData['_timestamp'] = now()->timestamp;

        // Broadcast pause event
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

        // Update cache via PlaybackStateManager
        $this->playbackStateManager->setPlaybackData($mix, $playbackData);
        $this->playbackStateManager->setPaused($mix, true);
        $this->playbackStateManager->setManualChange($mix);
    }
}
