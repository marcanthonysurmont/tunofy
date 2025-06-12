<?php

namespace App\Services\Collaboration;

use App\Events\CoDJUpdatedEvent;
use App\Events\PlaybackDataUpdatedEvent;
use App\Models\Mix;
use App\Models\User;
use App\Models\QueueSong;
use App\Services\Playback\PlaybackStateManager;
use App\Services\Spotify\SpotifyService;
use App\Services\Playback\SongPlaybackService;
use Illuminate\Support\Facades\Log;

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
        $currentlyPlaying = QueueSong::currentlyPlayingForMix($mix);

        if ($currentlyPlaying) {
            $this->playbackStateManager->set(
                $mix,
                'transition_track',
                $currentlyPlaying->song->spotify_id,
            );
        }

        // Update the database with co-DJ
        $mix->update(['co_dj_id' => $user->id]);

        // Dispatch events
        CoDJUpdatedEvent::dispatch($user, [
            'playing_track' => $currentlyPlaying ? $currentlyPlaying->song->spotify_id : null,
        ]);

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
        $currentlyPlaying = QueueSong::currentlyPlayingForMix($mix);
        $trackToResume = null;

        // Get current playback position if available
        $currentPosition = 0;
        try {
            if ($coDJ) {
                // FIX: Use getCurrentPlayback instead of getPlaybackData
                $playbackData = $this->spotifyService->getCurrentPlayback($coDJ);
                if (!empty($playbackData) && isset($playbackData['progress_ms'])) {
                    $currentPosition = $playbackData['progress_ms'];
                    Log::info("Captured current position {$currentPosition}ms for mix {$mix->id} before takeback");
                }
            }
        } catch (\Exception $e) {
            Log::error("Could not capture playback position: " . $e->getMessage());
        }

        // Store the track ID that was playing for later resumption
        if ($currentlyPlaying) {
            $this->playbackStateManager->set($mix, 'switch_track_id', $currentlyPlaying->song->spotify_id);
            $trackToResume = $currentlyPlaying->song->spotify_id;
            Log::info("Marked song {$currentlyPlaying->song->spotify_id} as the pre-switch active song for mix {$mix->id}");
        }

        // Update the mix relationship FIRST
        $mix->update(['co_dj_id' => null]);

        // Reset queue state for user switch - change playing to pending
        QueueSong::where('mix_id', $mix->id)
            ->where('status', 'playing')
            ->update(['status' => 'pending']);
        Log::info("Reset queue state for user switch on mix {$mix->id} - changed 'playing' to 'pending'");

        // CRITICAL: Set owner takeback flag for proper resumption flow
        $this->playbackStateManager->setRecentOwnerTakeback($mix, true);
        Log::info("Set recent owner takeback flag for mix {$mix->id}");

        // IMPORTANT: Set a non-zero position to prevent track skip detection
        // Position should be at least 1000ms (1 second) to avoid being treated as "track reset"
        $this->playbackStateManager->setPausedPosition($mix, max(1000, $currentPosition));
        Log::info("Saved position " . max(1000, $currentPosition) . "ms before pausing mix {$mix->id}");

        // Notify the removed co-DJ if available
        if ($coDJ) {
            CoDJUpdatedEvent::dispatch($coDJ, [
                'playing_track' => $currentlyPlaying ? $currentlyPlaying->song->spotify_id : null,
            ]);
        }

        // Pause the co-DJ's playback
        if ($coDJ) {
            $this->spotifyService->pausePlayback($coDJ);
        }

        // Clear various caches that might be causing UI inconsistencies
        $this->playbackStateManager->forget($mix, 'recent_takeback_track_change');
        $this->playbackStateManager->forget($mix, 'last_skip_timestamp');
        $this->playbackStateManager->forget($mix, 'playback_state');

        // Find and mark the target song as 'playing' so it's ready when Play is clicked
        if ($trackToResume) {
            $queueSong = QueueSong::where('mix_id', $mix->id)
                ->whereHas('song', function ($query) use ($trackToResume) {
                    $query->where('spotify_id', $trackToResume);
                })
                ->first();

            if ($queueSong) {
                $queueSong->update(['status' => 'playing']);
                Log::info("Re-marked song {$trackToResume} as 'playing' for manual resumption by owner");
            } else {
                Log::error("Failed to find song with spotify_id {$trackToResume} to mark as playing after co-DJ removal");
            }
        }

        // Set device ID as the last step
        $owner = $mix->user;
        $devices = $this->spotifyService->getUserDevices($owner);

        if (!empty($devices)) {
            $deviceId = null;

            // Try to find active device first
            foreach ($devices as $device) {
                if ($device['is_active']) {
                    $deviceId = $device['id'];
                    break;
                }
            }

            // If no active device, use first available
            if (!$deviceId && !empty($devices[0]['id'])) {
                $deviceId = $devices[0]['id'];
            }

            // Set device ID for resumption
            if ($deviceId) {
                $this->playbackStateManager->setDeviceId($mix, $deviceId);
                Log::info("Retrieved and set owner's device ID {$deviceId} for mix {$mix->id}");
            }
        }
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
