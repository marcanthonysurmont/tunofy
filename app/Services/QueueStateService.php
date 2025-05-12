<?php

namespace App\Services;

use App\Models\Mix;

class QueueStateService
{
    public function resetPosition(int $mixId): void
    {
        $mix = Mix::findOrFail($mixId);
        $playbackState = app(PlaybackStateManager::class);
        $playbackState->set($mix, 'queue_position');
    }
}
