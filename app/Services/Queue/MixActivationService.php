<?php

namespace App\Services\Queue;

use App\Models\Mix;
use App\Events\MixStatusChangedEvent;
use Illuminate\Support\Facades\Log;
use App\Jobs\PollSpotifyMixJob;

class MixActivationService
{
    public function __construct(protected QueueManagementService $queueService) 
    {}

    /**
     * Toggle a mix active state with consolidated handling of related operations
     */
    public function toggleMixActive(Mix $mix, bool $activate): array
    {
        if ($activate) {
            return $this->activateMix($mix);
        } else {
            return $this->deactivateMix($mix);
        }
    }

    /**
     * Activate a mix and handle all related operations in one place
     */
    protected function activateMix(Mix $mix): array
    {
        // Update mix status
        $mix->update(['is_active' => true]);

        // Log the activation
        Log::info("Mix {$mix->id} activated");

        // Broadcast event
        event(new MixStatusChangedEvent($mix, true));

        // Start polling directly (no longer relies on QueueService to do this)
        dispatch(new PollSpotifyMixJob($mix));

        return [
            'success' => true,
            'message' => 'Mix activated successfully'
        ];
    }

    /**
     * Deactivate a mix and handle all related cleanup
     */
    protected function deactivateMix(Mix $mix): array
    {
        // Update mix status
        $mix->update(['is_active' => false]);

        // Log the deactivation
        Log::info("Mix {$mix->id} deactivated");

        // Broadcast event
        event(new MixStatusChangedEvent($mix, false));

        return [
            'success' => true,
            'message' => 'Mix deactivated successfully'
        ];
    }
}
