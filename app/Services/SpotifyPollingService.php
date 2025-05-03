<?php

namespace App\Services;

use App\Models\Mix;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\PlaybackDataUpdated;
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
    public function pollPlayback(Mix $mix)
    {
        $cacheKey = "spotify:playback:{$mix->id}";

        try {
            // Get the mix owner (the user)
            $user = User::find($mix->user_id);

            // Get current data from Spotify - pass the User object instead of Mix
            $playbackData = $this->spotifyService->getCurrentPlayback($user);

            // Check if playback data is valid/not empty
            if (empty($playbackData) || !isset($playbackData['item'])) {
                Log::warning("Received empty or invalid playback data for mix {$mix->id}");

                // Optionally broadcast a "no playback" status
                event(new PlaybackDataUpdated($mix, ['status' => 'no_active_playback']));
                return null;
            }

            // Get the previous data from cache
            $previousData = Cache::get($cacheKey);

            // Detect significant changes to determine if we should broadcast
            $shouldBroadcast = $this->hasSignificantChanges($previousData, $playbackData);

            if ($shouldBroadcast) {
                Log::info("Detected changes in mix {$mix->id} - Reason: " . $this->changeReason);

                // Then pass the model to the event
                event(new PlaybackDataUpdated($mix, $playbackData));
            } else {
                Log::debug("Skipping broadcast for mix {$mix->id} - No significant changes");
            }

            // Always update the cache with a timestamp
            $playbackData['_timestamp'] = now()->timestamp;
            Cache::put($cacheKey, $playbackData, 60);

            return $playbackData;
        } catch (\Exception $e) {
            Log::error("Error polling playback for mix {$mix->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the cached playback data for a mix
     */
    public function getCachedPlayback(Mix $mix)
    {
        return Cache::get("spotify:playback:{$mix->id}");
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
