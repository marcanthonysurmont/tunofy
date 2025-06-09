<?php

namespace App\Services\Queue;

use App\Models\Mix;
use App\Models\QueueSong;
use App\Models\User;
use App\Models\PlaybackSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Spotify\SpotifyService;
use App\Services\Playback\PlaybackStateManager;
use App\Services\Playback\SongPlaybackService;
use App\Events\QueueStateUpdatedEvent;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Song;

/**
 * Central service for all queue operations
 *
 * This service acts as a state machine for the queue, ensuring
 * all transitions happen through a single service with clear,
 * well-defined operations.
 */
class QueueManagementService
{
    public function __construct(
        protected SpotifyService $spotifyService,
        protected SongPlaybackService $songPlaybackService,
        protected QueueBuilderService $queueBuilderService,
        protected PlaybackStateManager $playbackStateManager
    ) {
    }

    /**
     * Initialize queue for a mix
     */
    public function initializeQueue(Mix $mix): array
    {
        try {
            Log::info("Starting queue initialization for mix {$mix->id}");

            // Clear existing queue
            $this->clearQueue($mix->id);
            Log::info("Cleared existing queue for mix {$mix->id}");

            // Create a new playback session
            $rawSession = PlaybackSession::create([
                'mix_id' => $mix->id,
                'started_at' => now(),
                'is_active' => true
            ]);

            // IMPORTANT: Convert to array immediately to avoid any accidental property access
            $session = is_array($rawSession) ? $rawSession : $rawSession->toArray();

            if (empty($session['id'])) {
                throw new Exception("Failed to create session with valid ID");
            }

            $sessionId = $session['id'];

            // Proceed with queue initialization using the array access only
            $shuffledIds = $this->queueBuilderService->getShuffledSongIds($mix);

            Cache::put("mix_{$mix->id}_shuffled_ids", $shuffledIds, now()->addHours(6));

            $initialRounds = max(2, ceil(12 / $mix->preset->batch_size));

            // Get initial songs for the queue
            $queueBatches = $this->queueBuilderService->buildQueue($mix, $initialRounds);

            $queueBatchesCount = count($queueBatches);
            $songCount = 0;

            foreach ($queueBatches as $roundNumber => $songs) {
                foreach ($songs as $index => $song) {
                    // Check if song is a model or array
                    if (is_array($song)) {
                        $songId = $song['id'] ?? null;
                        if (!$songId) {
                            Log::error("Invalid song array in queue initialization: " . json_encode($song));
                            continue;
                        }
                    } else {
                        $songId = $song->id;
                    }

                    QueueSong::create([
                        'song_id' => $songId,
                        'mix_id' => $mix->id,
                        'playback_session_id' => $sessionId,
                        'round_number' => $roundNumber,
                        'order' => $index + 1,
                        'status' => 'pending'
                    ]);

                    $songCount++;
                }
            }

            // Reset queue position
            $this->playbackStateManager->resetQueuePosition($mix);

            Log::info("Queue initialization complete with {$queueBatchesCount} batches and {$songCount} songs");

            return [
                'success' => true,
                'message' => 'Queue initialized successfully',
                'session_id' => $sessionId
            ];
        } catch (Exception $e) {
            Log::error("Failed to initialize queue: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Failed to initialize queue: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Start or resume queue playback
     */
    public function startPlayback(Mix $mix, ?string $deviceId = null): bool
    {
        // Get the current playing song directly - minimal query
        $currentSong = QueueSong::currentlyPlayingForMix($mix);

        if (!$currentSong) {
            Log::error("No playing song found for mix $mix->id during startPlayback");
            return false;
        }

        // Get user
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        // Call Spotify API directly - no other operations
        try {
            // Direct API call without any intermediate steps
            $playTrackOnDevice = $this->spotifyService->playTrackOnDevice(
                $user,
                $currentSong->song->spotify_id,
                $deviceId
            );

            QueueStateUpdatedEvent::dispatch($mix);

            return $playTrackOnDevice;

        } catch (Exception $e) {
            Log::error("Error in QueueManagementService::startPlayback: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear the entire queue for a mix
     */
    public function clearQueue(int $mixId): void
    {
        QueueSong::where('mix_id', $mixId)
            ->whereIn('status', ['playing', 'pending'])
            ->update([
                'status' => 'finished',
                'played_at' => Carbon::now()
            ]);

        Log::info("Cleared queue for mix {$mixId}");
    }

    public function appendRoundsToQueue(Mix $mix, int $numRounds = 1): array
    {
        // Get songs that are in the shuffle cache but not yet queued
        $shuffledIds = Cache::get("mix_{$mix->id}_shuffled_ids", []);
        if (empty($shuffledIds)) {
            Log::info("Queue extension for mix {$mix->id}: Found 0 songs in shuffled IDs cache");
            return ['success' => false, 'message' => 'No shuffled IDs found'];
        }

        // Get IDs of songs already in the queue
        $queuedSongIds = QueueSong::where('mix_id', $mix->id)->pluck('song_id')->toArray();

        // Filter out songs already in the queue
        $nonQueuedSongIds = array_diff($shuffledIds, $queuedSongIds);

        // Only get enough songs to fill the requested number of rounds
        $batchSize = $mix->preset->batch_size ?? 5;
        $songsNeeded = $numRounds * $batchSize;
        $availableIds = $nonQueuedSongIds;
        shuffle($availableIds); // Randomly shuffle the available song IDs
        $songIdsToQueue = array_slice($availableIds, 0, $songsNeeded);

        if (empty($songIdsToQueue)) {
            Log::info("Queue extension for mix {$mix->id}: Found 0 songs not yet queued out of " . count($shuffledIds) . " total shuffled songs");
            return ['success' => false, 'message' => 'No additional songs to queue'];
        }

        Log::info("Queue extension for mix {$mix->id}: Found " . count($songIdsToQueue) . " songs not yet queued out of " . count($shuffledIds) . " total shuffled songs");

        // Get current playing round with a direct query that guarantees accuracy
        $playingRound = QueueSong::PlayingRound($mix);

        // If no song is currently playing, check recently played songs to determine the active round
        if (!$playingRound) {
            // Try to determine the current round from recently played songs
            $lastPlayedSong = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'played')
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($lastPlayedSong) {
                $playingRound = $lastPlayedSong->round_number;
                Log::info("Queue extension: No currently playing song found, using last played round: {$playingRound}");
            } else {
                // If no played songs either, check pending songs
                $lowestPendingRound = QueueSong::where('mix_id', $mix->id)
                    ->where('status', 'pending')
                    ->min('round_number');

                if ($lowestPendingRound) {
                    // Assume the round before the lowest pending is either playing or just finished
                    $playingRound = $lowestPendingRound;
                    Log::info("Queue extension: No playing or played songs found, using lowest pending round as active: {$playingRound}");
                } else {
                    // Truly empty queue
                    $playingRound = 0;
                    Log::info("Queue extension: Completely empty queue for mix {$mix->id}, starting with round 1");
                }
            }
        }

        // Log the playing round for debugging
        Log::info("Queue extension: Final determined playing round for mix {$mix->id} is {$playingRound}");

        // Get all pending rounds with their song counts
        $pendingRounds = QueueSong::select('round_number', DB::raw('COUNT(*) as song_count'))
            ->where('mix_id', $mix->id)
            ->where('status', 'pending')
            ->groupBy('round_number')
            ->orderBy('round_number')
            ->get();

        $targetRound = null;
        $songsInRound = 0;
        $nextRoundAfterPlaying = $playingRound + 1;

        // First check if there is a non-full round AFTER the playing round
        foreach ($pendingRounds as $round) {
            // Skip rounds that are currently playing or have already been played
            if ($round->round_number <= $playingRound) {
                Log::info("Queue extension: Skipping round {$round->round_number} because it's playing or already played");
                continue;
            }

            // Use this round if it's not full and it's AFTER the playing round
            if ($round->song_count < $batchSize) {
                $targetRound = $round->round_number;
                $songsInRound = $round->song_count;
                Log::info("Queue extension: Using existing pending round {$targetRound} for mix {$mix->id} ({$songsInRound}/{$batchSize} songs)");
                break;
            }
        }

        // If we couldn't find a suitable round, create a new one
        if ($targetRound === null) {
            // Find the highest round number (even if it's not pending)
             $highestRound = QueueSong::where('mix_id', $mix->id)
                ->max('round_number') ?? 0;

            $targetRound = max($highestRound + 1, $nextRoundAfterPlaying);
            $songsInRound = 0;
            Log::info("Queue extension: Creating NEW round {$targetRound} for mix {$mix->id}");
        }

        $session = $mix->playbackSession;

        if (!$session) {
            Log::error("Queue extension failed: No active session for mix {$mix->id}");
            return ['success' => false, 'message' => 'No active session'];
        }

        $added = 0;

        // Process each song ID directly without loading full song objects
        foreach ($songIdsToQueue as $songId) {
            // Check if this round is full, if so, move to next round
            if ($songsInRound >= $batchSize) {
                $targetRound++;
                $songsInRound = 0;
                $previousRound = $targetRound - 1;
                Log::info("Queue extension: Round {$previousRound} full, moving to round {$targetRound}");
            }

            // Calculate position in the round
            $position = $songsInRound + 1;

            // Add to queue using just the ID
            QueueSong::create([
                'mix_id' => $mix->id,
                'song_id' => $songId,
                'playback_session_id' => $session->id,
                'round_number' => $targetRound,
                'order' => $position,
                'status' => 'pending',
                'is_killed' => false,
            ]);

            Log::info("Queue extension: Added song {$songId} to queue for mix {$mix->id} in round {$targetRound} (position {$position}/{$batchSize})");

            $songsInRound++;
            $added++;
        }

        if ($added > 0) {
            event(new QueueStateUpdatedEvent($mix));
        }

        return [
            'success' => true,
            'added' => $added,
            'message' => "Added {$added} songs to queue"
        ];
    }

    /**
     * Add a song directly to the queue if the pending count is below threshold
     */
    public function addSongToQueueIfBelowThreshold(Mix $mix, Song $song, Playbacksession $session): void
    {
        $batchSize = $mix->preset->batch_size ?? 5;
        $pendingSongCount = QueueSong::PendingForMixCount($mix);
        $directAdditionThreshold = max(5, $batchSize);

        // Only add directly if we're under the threshold
        if ($pendingSongCount > $directAdditionThreshold) {
            return;
        }

        // Get current playing round
        $playingRound = QueueSong::PlayingRound($mix);

        // Get latest round number
        $latestRound = QueueSong::where('mix_id', $mix->id)
            ->max('round_number') ?? 0;

        // Find a suitable round
        $targetRound = $this->findSuitableRound($mix, $playingRound, $batchSize);

        if ($targetRound === null) {
            $targetRound = $playingRound ? ($playingRound + 1) : ($latestRound + 1);
            Log::info("Creating new round {$targetRound} for song {$song->id}");
        }

        // Get order in round
        $songsInTargetRound = QueueSong::where('mix_id', $mix->id)
            ->where('round_number', $targetRound)
            ->count();
        $nextOrder = $songsInTargetRound + 1;

        try {
            // Add directly to queue
            QueueSong::create([
                'mix_id' => $mix->id,
                'song_id' => $song->id,
                'playback_session_id' => $session->id,
                'round_number' => $targetRound,
                'order' => $nextOrder,
                'status' => 'pending',
                'is_killed' => false,
            ]);

            Log::info("Added song {$song->id} directly to queue for mix {$mix->id} in round {$targetRound} (position {$nextOrder}/{$batchSize})");

            // Dispatch an event to update clients
            event(new QueueStateUpdatedEvent($mix));
    
            return;
        } catch (Exception $e) {
            Log::error("Error adding song {$song->id} to queue: " . $e->getMessage());
            return;
        }
    }

    /**
     * Find suitable round for direct song addition
     */
    private function findSuitableRound(Mix $mix, ?int $playingRound, int $batchSize): ?int
    {
        // Get all pending rounds ordered by round_number
        $pendingRounds = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'pending')
            ->groupBy('round_number')
            ->pluck('round_number')
            ->sort()
            ->values();

        foreach ($pendingRounds as $round) {
            if ($round <= $playingRound) {
                continue;
            }

            $songsInRound = QueueSong::where('mix_id', $mix->id)
                ->where('round_number', $round)
                ->count();

            if ($songsInRound < $batchSize) {
                Log::info("Using existing pending round {$round} for direct song addition");
                return $round;
            }
        }

        return null;
    }

    /**
     * Check if queue needs extending and extend if necessary
     */
    public function extendQueueIfNeeded(Mix $mix): bool
    {
        $batchSize = $mix->preset->batch_size ?? 5;
        $pendingSongCount = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'pending')
            ->count();

        $threshold = max(3, $batchSize * 0.5);

        if ($pendingSongCount <= $threshold) {
            try {
                $this->appendRoundsToQueue($mix, 1);
                Log::info("Extended queue after adding new song to mix {$mix->id}");
                return true;
            } catch (Exception $e) {
                Log::error("Error extending queue: " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    protected function getActiveSessionId(Mix $mix): ?int
    {
        $session = PlaybackSession::where('mix_id', $mix->id)
            ->where('is_active', true)
            ->latest('started_at')
            ->first();

        if (!$session) {
            // Create a new session if none exists
            $session = PlaybackSession::create([
                'mix_id' => $mix->id,
                'started_at' => now(),
                'is_active' => true
            ]);
            Log::info("Created new playback session {$session->id} for mix {$mix->id} during appendRoundsToQueue");
        }

        // Add the same defensive check here
        if (is_array($session)) {
            Log::warning("Session was returned as array instead of object in getActiveSessionId");
            return $session['id'] ?? null;
        }

        return $session->id;
    }
}
