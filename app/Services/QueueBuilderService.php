<?php

namespace App\Services;

use App\Models\Mix;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QueueBuilderService
{
    public function buildQueue(Mix $mix, int $rounds, int $offset = 0): array
    {
        $batchSize = $mix->preset->batch_size;
        $limit = $rounds * $batchSize;

        $shuffledIds = Cache::get("mix_{$mix->id}_shuffled_ids", []);

        if (empty($shuffledIds)) {
            Log::warning("No shuffled IDs found for mix {$mix->id}");
            return [];
        }

        $selectedIds = array_slice($shuffledIds, $offset, $limit);
        $songs = $mix->songs()->whereIn('id', $selectedIds)->get()->keyBy('id');

        $ordered = [];
        foreach ($selectedIds as $id) {
            if (isset($songs[$id])) {
                $ordered[] = $songs[$id];
            } else {
                Log::warning("Song ID {$id} not found in mix {$mix->id}");
            }
        }

        if (empty($ordered)) {
            Log::warning("No songs found for mix {$mix->id} after ordering");
            return [];
        }

        Log::debug("Building queue for mix {$mix->id}: Found " . count($ordered) . " songs to queue");

        $result = $this->assignRounds($ordered, $batchSize);

        // Check if we got any rounds
        if (empty($result)) {
            Log::warning("Queue builder returned empty result for mix {$mix->id}");
        } else {
            Log::debug("Queue builder created " . count($result) . " rounds for mix {$mix->id}");
        }

        return $result;
    }



    protected function randomizeSongsWithBias($songs)
    {
        // Re-seed random number generator for better entropy
        mt_srand((int)(microtime(true) * 1000));

        // First shuffle to break any initial ordering
        $songsArray = $songs->shuffle()->all();
        $songsCollection = collect($songsArray);

        return $songsCollection
            ->map(function ($song) {
                // For newer songs, days_old will be smaller
                $daysOld = now()->diffInDays($song->created_at ?? now());

                // Convert to a score where newer songs get HIGHER values
                // 1 day old = 0.9, 10 days old = 0.5, 90 days old = 0.1, etc.
                $newness = 1 / (1 + (0.1 * $daysOld));

                // Add randomness (30% random, 70% newness-based)
                $randomFactor = mt_rand() / mt_getrandmax() * 0.3;

                return [
                    'song' => $song,
                    'score' => ($newness * 0.7) + $randomFactor,
                    'days_old' => $daysOld // For debugging
                ];
            })
            ->sortByDesc('score')  // Higher scores (newer songs) come first
            ->pluck('song')
            ->values();
    }

    protected function randomizeSongsFlat($songs)
    {
        return $songs
            ->map(function ($song) {
                $score = mt_rand() / mt_getrandmax();
                $noise = mt_rand() / mt_getrandmax() * 0.01;

                return [
                    'song' => $song,
                    'score' => $score + $noise
                ];
            })
            ->sortByDesc('score')
            ->pluck('song')
            ->values();
    }



    /**
     * Assign songs to rounds
     */
    private function assignRounds($songs, int $batchSize): array
    {
        // Make sure we're working with a Collection
        $songsCollection = is_array($songs) ? collect($songs) : $songs;

        // Now use the collection methods
        $chunks = $songsCollection->chunk($batchSize);

        $result = [];
        foreach ($chunks as $index => $chunk) {
            $result[$index + 1] = $chunk->values()->all();
        }

        return $result;
    }

    public function getShuffledSongIds(Mix $mix): array
    {
        // Get all songs for the mix
        $songs = $mix->songs;

        if (count($songs) === 0) {
            Log::warning("Mix {$mix->id} has no songs to shuffle");
            return [];
        }

        // Check if we should prioritize newer songs
        $prioritizeNew = $mix->preset->priority_boost_new ?? false;

        // Apply appropriate randomization strategy
        if ($prioritizeNew) {
            $shuffled = $this->randomizeSongsWithBias($songs);
        } else {
            $shuffled = $this->randomizeSongsFlat($songs);
        }

        // Get the IDs from the shuffled songs
        $ids = $shuffled->pluck('id')->toArray();

        return $ids;
    }

}
