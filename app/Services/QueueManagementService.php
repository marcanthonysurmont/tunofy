<?php

namespace App\Services;

use App\Events\PlaybackDataUpdatedEvent;
use App\Models\Mix;
use App\Models\QueueSong;
use App\Models\User;
use App\Models\PlaybackSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\SpotifyService;
use App\Services\SongPlaybackService;
use App\Services\QueueBuilderService;
use App\Events\DeviceUpdatedEvent;

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
    public function startPlayback(int $mixId, bool $resetQueue = false, ?string $deviceId = null): array
    {
        // Get the active session
        $session = PlaybackSession::where('mix_id', $mixId)
            ->where('is_active', true)
            ->latest('started_at')
            ->first();

        // Add defensive check here too
        $sessionId = null;
        if (is_array($session)) {
            Log::warning("Session was returned as array instead of object in startPlayback");
            $sessionId = $session['id'] ?? null;
        } elseif ($session) {
            $sessionId = $session->id;
        }

        if (!$sessionId) {
            // Create new session if none exists
            $sessionObj = PlaybackSession::create([
                'mix_id' => $mixId,
                'started_at' => now(),
                'is_active' => true
            ]);

            if (is_array($sessionObj)) {
                $sessionId = $sessionObj['id'];
            } else {
                $sessionId = $sessionObj->id;
            }

            if (!$sessionId) {
                Log::error("Failed to create valid session in startPlayback for mix {$mixId}");
                return ['success' => false, 'message' => 'Session creation failed'];
            }
        }

        // Use $sessionId instead of $session->id

        Log::info("Starting playback for mix {$mixId}, resetQueue: " . ($resetQueue ? 'true' : 'false') .
                  ($deviceId ? ", deviceId: {$deviceId}" : ""));

        // Get the mix and user
        $mix = Mix::findOrFail($mixId);
        $user = User::findOrFail(Auth::id() ?? $mix->user_id);

        // If device ID is provided, store it
        if ($deviceId) {
            // Make sure this key format matches what you're checking in SpotifyPollingService
            Cache::put("mix:{$mix->id}:device_id", $deviceId, now()->addHours(1));

            // Set the device changed flag
            Cache::put("mix:{$mix->id}:device_changed", true, now()->addSeconds(5));
        }

        // If device ID is provided, activate it FIRST
        if ($deviceId) {
            // Activate the device via the SpotifyService and make sure it's fully ready
            // This is the key change - force the device activation to complete before playing
            $activated = $this->spotifyService->activateSpecificDevice($user, $deviceId);

            // Add a small extra delay for desktop clients
            usleep(200000); // 200ms extra delay

            if (!$activated) {
                Log::warning("Device activation failed, falling back to default device");
            }
        } else {
            // No specific device, use default behavior
            $this->spotifyService->activateDevice($user);
        }

        // If resetQueue is true, ensure we start from the beginning
        if ($resetQueue) {
            // Reset the position to ensure we start from the first song
            Cache::put("mix_{$mixId}_queue_position", 0, 3600);
            Log::info("Queue position reset to 0 for mix {$mixId} before playback");
        }

        // Send an IMMEDIATE simplified activation event to update UI faster
        $simpleActivationData = [
            'is_playing' => true,
            'is_initial_activation' => true,
            '_timestamp' => now()->timestamp,
            'item' => [
                'name' => 'Starting playback...',
                'artists' => [['name' => 'Your mix is starting']],
                'album' => ['images' => []]
            ]
        ];

        // Broadcast this simple event immediately
        Log::info("Broadcasting immediate activation signal for mix {$mixId}");
        event(new PlaybackDataUpdatedEvent($mix, $simpleActivationData));

        // Then use the standard playback method, passing the deviceId
        $result = $this->songPlaybackService->startPlayback($mixId, $deviceId);

        // Add this check:
        if (!$result['success']) {
            // If playback failed and we were trying to use a specific device
            if ($deviceId) {
                Log::error("Failed to start playback on device {$deviceId} for mix {$mixId}");
                Cache::put("mix:{$mixId}:device_failure", true);
                event(new DeviceUpdatedEvent($mix));
            }
            return $result;
        }

        return $result;
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

    /**
     * Clear all cache for a mix except for device selection
     */
    public function clearMixCache(int $mixId): void
    {
        // Remember the device ID before clearing cache
        $deviceId = Cache::get("mix:{$mixId}:device_id");

        // Clear specific prefixed keys without relying on patterns
        $keysToForget = [
            "mix:{$mixId}:paused",
            "mix:{$mixId}:device_changed",
            "mix:{$mixId}:device_mismatch",
            "mix_{$mixId}_queue_position"
        ];

        foreach ($keysToForget as $key) {
            Cache::forget($key);
        }

        // IMPORTANT: Restore the device ID if it existed
        if ($deviceId) {
            Cache::put("mix:{$mixId}:device_id", $deviceId, now()->addDay());
            Log::info("Preserved device ID {$deviceId} for mix {$mixId} during cache clearing");
        }

        Log::info("Cleared cache entries for mix {$mixId} while preserving device selection");
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
