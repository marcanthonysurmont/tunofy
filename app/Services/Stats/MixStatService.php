<?php

namespace App\Services\Stats;

use App\Models\Mix;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MixStatService
{
    /**
     * Calculate the formatted duration for a mix
     */
    public function calculateMixDuration(HasMany $songsQuery): callable
    {
        $totalDuration = (clone $songsQuery)->sum('duration_ms');

        return function () use ($totalDuration) {
            $hours = floor($totalDuration / 3600000);
            $minutes = floor(($totalDuration % 3600000) / 60000);
            return ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'min';
        };
    }

    /**
     * Get pending songs sorted by votes
     */
    public function getSortedPendingSongs(Mix $mix): Collection
    {
        $pendingSongs = $mix->getAllPendingSongs();

        return $pendingSongs->sortByDesc(function ($song) {
            return ($song->like_count ?? 0) - ($song->dislike_count ?? 0);
        })->values();
    }
}
