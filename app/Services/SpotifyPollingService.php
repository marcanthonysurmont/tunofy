<?php

namespace App\Services;

use App\Models\Mix;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\PlaybackDataUpdatedEvent;
use App\Models\User;

class SpotifyPollingService
{
    protected $spotifyService;
    protected $changeReason = ''; // Add this property to store reason for changes

    public function __construct(SpotifyService $spotifyService)
    {
        $this->spotifyService = $spotifyService;
    }

    /**
     * Poll Spotify for the current playback state of a mix's owner
     */
    public function pollPlayback(Mix $mix): void
    {
        $cacheKey = "spotify:playback:{$mix->id}";

        try {
            // Get the mix owner
            $user = User::find($mix->user_id);
            Log::info("Requesting playback data from Spotify for user {$user->id}");

            // Get current data from Spotify
            $playbackData = $this->spotifyService->getCurrentPlayback($user);

            // Handle case when nothing is playing
            if (empty($playbackData) || !isset($playbackData['item'])) {
                Log::info("No active playback for user {$user->id}");

                // Broadcast a "no playback" status with timestamp
                $noPlaybackData = [
                    'status' => 'no_active_playback',
                    '_timestamp' => now()->timestamp
                ];

                // Update cache and broadcast
                Cache::put($cacheKey, $noPlaybackData);
                event(new PlaybackDataUpdatedEvent($mix, $noPlaybackData));
                
                return;
            }

            // Get the previous data from cache
            $previousData = Cache::get($cacheKey);

            // Always add timestamp to the current data
            $playbackData['_timestamp'] = now()->timestamp;

            // Update cache regardless of changes to keep timestamp fresh
            Cache::put($cacheKey, $playbackData);

            // Only broadcast if there are significant changes
            $shouldBroadcast = $this->hasSignificantChanges($previousData, $playbackData);
            if ($shouldBroadcast) {
                Log::info("Detected changes in mix {$mix->id} - Reason: " . $this->changeReason);
                event(new PlaybackDataUpdatedEvent($mix, $playbackData));
            } else {
                Log::debug("Skipping broadcast for mix {$mix->id} - No significant changes");
            }
        } catch (\Exception $e) {
            Log::error("Error polling playback for mix {$mix->id}: " . $e->getMessage());
        }
    }

    /**
     * Determine if there are significant changes between previous and current playback data
     */
    private function hasSignificantChanges($previous, $current)
    {
        if (!$previous) {
            $this->changeReason = "first broadcast";
            return true;
        }

        // Only these two changes matter
        if (($previous['is_playing'] ?? false) !== ($current['is_playing'] ?? false)) {
            $this->changeReason = "play state changed";
            return true;
        }

        if (($previous['item']['id'] ?? null) !== ($current['item']['id'] ?? null)) {
            $this->changeReason = "track changed";
            return true;
        }

        return false;
    }
}
