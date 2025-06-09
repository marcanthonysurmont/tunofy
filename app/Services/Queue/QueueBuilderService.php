<?php

namespace App\Services\Queue;

use App\Models\Mix;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\QueueSong;
use App\Models\Song;

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



    protected function randomizeSongsWithBias(Collection $songs): Collection
    {
        // Re-seed random number generator for better entropy
        mt_srand((int)(microtime(true) * 10000));

        // First shuffle to break any initial ordering
        $songsArray = $songs->shuffle()->all();
        $songsCollection = collect($songsArray);

        return $songsCollection
            ->map(function ($song) {
                // For newer songs, days_old will be smaller
                $daysOld = now()->diffInDays($song->created_at ?? now());

                // Convert to a score where newer songs get HIGHER values
                $newness = 1 / (1 + (0.1 * $daysOld));

                // Add more randomness (50% random, 50% newness-based) for better distribution
                $randomFactor = mt_rand() / mt_getrandmax() * 0.5;

                return [
                    'song' => $song,
                    'score' => ($newness * 0.5) + $randomFactor,
                    'days_old' => $daysOld
                ];
            })
            ->sortByDesc('score')  // Higher scores (newer songs) come first
            ->pluck('song')
            ->values();
    }

    protected function randomizeSongsFlat(Collection $songs): Collection
    {
        // First, re-seed the random number generator with a high-precision timestamp
        mt_srand((int)(microtime(true) * 10000));

        // First do a native shuffle to break any initial ordering
        $shuffledArray = $songs->shuffle()->all();
        $shuffled = collect($shuffledArray);

        // Then apply scoring for a second layer of randomization
        return $shuffled
            ->map(function ($song) {
                // Use a higher noise factor (0.5 instead of 0.01) for more randomness
                $score = mt_rand() / mt_getrandmax();
                $noise = mt_rand() / mt_getrandmax() * 0.5;

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
    private function assignRounds(array $songs, int $batchSize): array
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
        $songs = $mix->songs()->inRandomOrder()->get();  // Add inRandomOrder() for extra randomness

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

        // Add a final shuffle for extra randomness
        shuffle($ids);

        // **** THIS IS THE CRITICAL LINE YOU'RE MISSING ****
        Cache::put("mix_{$mix->id}_shuffled_ids", $ids, now()->addHours(6));

        // Log first 5 for verification
        Log::info("Mix {$mix->id} shuffled IDs (first 5): " . implode(', ', array_slice($ids, 0, 5)));

        return $ids;
    }

    /**
     * Update the shuffled IDs cache to include a new song
     *
     */
    public function updateShuffledIdsCache(Mix $mix, Song $song): void
    {
        $shuffledIds = Cache::get("mix_{$mix->id}_shuffled_ids", []);
        if (empty($shuffledIds)) {
            return;
        }

        // Add the new song ID to the cache
        $shuffledIds[] = $song->id;

        // Add at a semi-random position (in the first half of non-queued songs)
        if (count($shuffledIds) > 1) {
            $queuedSongIds = QueueSong::where('mix_id', $mix->id)->pluck('song_id')->toArray();
            $nonQueuedSongIds = array_diff($shuffledIds, $queuedSongIds);

            if (count($nonQueuedSongIds) > 1) {
                array_pop($shuffledIds);
                $insertPosition = count($queuedSongIds) + rand(0, floor(count($nonQueuedSongIds) / 2));
                array_splice($shuffledIds, $insertPosition, 0, [$song->id]);
                Log::info("Added song ID {$song->id} at position {$insertPosition} in the shuffled IDs cache for mix {$mix->id}");
            }
        }

        // Save the updated shuffled IDs
        Cache::put("mix_{$mix->id}_shuffled_ids", $shuffledIds);
    }
}
