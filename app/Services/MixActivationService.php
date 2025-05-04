<?php

namespace App\Services;

use App\Models\Mix;
use App\Events\MixStatusChanged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Jobs\PollSpotifyMix;

class MixActivationService
{
    public function toggleMixActive(Mix $mix, bool $isActive): array
    {
        $wasActive = $mix->is_active;
        
        Log::info("Setting mix {$mix->id} active status: {$wasActive} → {$isActive}");

        // Update the mix active status
        $mix->update(['is_active' => $isActive]);

        // Broadcast the status change
        Log::info("Broadcasting MixStatusChanged event for mix {$mix->id}, active={$isActive}");
        broadcast(new MixStatusChanged($mix, $isActive))->toOthers();

        // Handle deactivation
        if (!$isActive && $wasActive) {
            $this->handleDeactivation($mix);
        }

        // Handle activation
        if ($isActive && !$wasActive) {
            $this->handleActivation($mix);
        }

        return [
            'success' => true,
            'is_active' => (bool) $mix->is_active,
        ];
    }

    private function handleDeactivation(Mix $mix): void
    {
        Log::info("Clearing cache for deactivated mix {$mix->id}");
        Cache::forget("spotify:playback:{$mix->id}");
    }

    private function handleActivation(Mix $mix): void
    {
        Log::info("Dispatching polling job for mix {$mix->id}");
        PollSpotifyMix::dispatch($mix);
    }
}