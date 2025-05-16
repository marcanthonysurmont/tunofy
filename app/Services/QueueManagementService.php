<?php

namespace App\Services;

use App\Models\Mix;
use App\Models\QueueSong;
use App\Models\User;
use App\Models\PlaybackSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
    public function initializeQueue(Mix $mix, bool $resetQueue = false): array
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
                throw new \Exception("Failed to create session with valid ID");
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
        } catch (\Exception $e) {
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
    public function startPlayback(int $mixId, bool $resetQueue = false, ?string $deviceId = null): bool
    {
        $mix = Mix::findOrFail($mixId);

        // Get the current playing song directly - minimal query
        $currentSong = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->with('song')
            ->first();

        if (!$currentSong) {
            Log::error("No playing song found for mix $mixId during startPlayback");
            return false;
        }

        // Get user
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        // Call Spotify API directly - no other operations
        try {
            $spotifyService = app(SpotifyService::class);

            // Direct API call without any intermediate steps
            return $spotifyService->playTrackOnDevice(
                $user,
                $currentSong->song->spotify_id,
                $deviceId
            );
        } catch (\Exception $e) {
            Log::error("Error in QueueManagementService::startPlayback: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Stop playback and clear the queue
     */
    public function stopPlayback(int $mixId): array
    {
        $mix = Mix::findOrFail($mixId);

        // Determine which user to use for playback
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        Log::info("Stopping playback for mix {$mixId}");

        // First, try to pause Spotify playback
        try {
            $this->spotifyService->pausePlayback($user);
            Log::info("Paused Spotify playback for mix {$mixId}");
        } catch (\Exception $e) {
            Log::error("Failed to pause Spotify playback: " . $e->getMessage());
            // Continue with queue cleanup even if pause fails
        }

        // Clear the queue
        $this->clearQueue($mixId);

        // End active sessions
        PlaybackSession::where('mix_id', $mixId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now()
            ]);

        return [
            'success' => true,
            'message' => 'Playback stopped and queue cleared'
        ];
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

    /**
     * Activate Spotify device
     */
    public function activateSpotifyDevice(int $userId)
    {
        try {
            // Get the user
            $user = User::findOrFail($userId);

            // URI for a very short track (could be any short track)
            $silentTrackUri = "spotify:track:4pgUMboZpbJBWE5ep2O77p"; // Replace with actual track URI

            // Play at very low volume
            $this->spotifyService->setVolume($user, 1);

            // Play the activation track
            $result = $this->spotifyService->playSong($user, $silentTrackUri);

            // Wait a moment
            sleep(1);

            // Restore volume
            $this->spotifyService->setVolume($user, 50);

            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to activate Spotify device: " . $e->getMessage());
            return false;
        }
    }

    public function appendRoundsToQueue(Mix $mix, int $rounds = 1): array
    {
        // Get current max round number
        $lastRound = QueueSong::where('mix_id', $mix->id)->max('round_number') ?? 0;

        // Get offset so we don't re-add already-queued songs
        $totalQueued = QueueSong::where('mix_id', $mix->id)->count();

        if (!Cache::has("mix_{$mix->id}_shuffled_ids")) {
            $shuffledIds = $this->queueBuilderService->getShuffledSongIds($mix);
            Cache::put("mix_{$mix->id}_shuffled_ids", $shuffledIds, now()->addHours(6));
        }

        // Build next N rounds from that point
        $queueBatches = $this->queueBuilderService->buildQueue($mix, $rounds, $offset = $totalQueued);

        // IMPORTANT: For each round, reset the order counter to 1
        $sessionId = $this->getActiveSessionId($mix);

        foreach ($queueBatches as $round => $songs) {
            // Start order from 1 for each round
            $orderInRound = 1;

            foreach ($songs as $song) {
                QueueSong::create([
                    'mix_id' => $mix->id,
                    'song_id' => $song->id,
                    'playback_session_id' => $sessionId,
                    'round_number' => $lastRound + $round,
                    'order' => $orderInRound++, // Use order within round, then increment
                    'status' => 'pending',
                    'is_killed' => false,
                ]);
            }
        }

        return [
            'success' => true,
            'message' => "Appended {$rounds} more rounds to the queue"
        ];
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
