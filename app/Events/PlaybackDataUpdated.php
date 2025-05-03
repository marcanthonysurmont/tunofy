<?php

namespace App\Events;

use App\Models\Mix;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PlaybackDataUpdated implements ShouldBroadcastNow
{
    public $mixId;
    public $playbackData;

    public function __construct(Mix $mix, $playbackData)
    {
        $this->mixId = $mix->id;
        $this->playbackData = $playbackData;
    }

    public function broadcastOn()
    {
        return new Channel('mix.' . $this->mixId);
    }

    public function broadcastAs()
    {
        return 'playback-data';
    }

    public function broadcastWhen()
    {
        // Use ONLY ONE cache key
        $cacheKey = "spotify:playback:{$this->mixId}";

        $lastData = Cache::get($cacheKey);

        // First broadcast - always send
        if (!$lastData) {
            // Update the shared cache
            $this->playbackData['_timestamp'] = now()->timestamp;
            Cache::put($cacheKey, $this->playbackData, 300);

            Log::info("Broadcasting playback update for mix {$this->mixId} - Reason: first broadcast");
            return true;
        }

        // Check for meaningful changes
        $hasChanges = false;
        $reason = "";

        // Play state changed
        if (($lastData['is_playing'] ?? false) !== ($this->playbackData['is_playing'] ?? false)) {
            $hasChanges = true;
            $reason = "play state changed";
        }
        // Track changed
        elseif (($lastData['item']['id'] ?? null) !== ($this->playbackData['item']['id'] ?? null)) {
            $hasChanges = true;
            $reason = "track changed";
        }

        if ($hasChanges) {
            // Update the shared cache
            $this->playbackData['_timestamp'] = now()->timestamp;
            Cache::put($cacheKey, $this->playbackData, 300);

            Log::info("Broadcasting playback update for mix {$this->mixId} - Reason: {$reason}");
            return true;
        }

        // Always update the timestamp even for non-broadcast updates
        $this->playbackData['_timestamp'] = now()->timestamp;
        Cache::put($cacheKey, $this->playbackData, 300);

        Log::debug("Skipping broadcast for mix {$this->mixId} - No significant changes");
        return false;
    }
}
