<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class QueueStateService
{
    public function getPosition(int $mixId): int
    {
        return Cache::get("mix:{$mixId}:queue_position", 0);
    }

    public function resetPosition(int $mixId): void
    {
        Cache::put("mix:{$mixId}:queue_position", 0, 3600);
    }

    public function incrementPosition(int $mixId): void
    {
        Cache::increment("mix:{$mixId}:queue_position");
    }
}
