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

/**
 * Central service for all queue operations
 *
 * This service acts as a state machine for the queue, ensuring
 * all transitions happen through a single service with clear,
 * well-defined operations.
 */
class QueueManagementService
{
    public function __construct(protected SpotifyService $spotifyService, protected SongPlaybackService $songPlaybackService)
    {
    }

    /**
     * Initialize queue for a mix
     *
     * Creates queue entries for all songs in the mix.
     */
    public function initializeQueue(Mix $mix, bool $resetQueue = false): array
    {
        Log::info("Initializing queue for mix {$mix->id}");

        // Clean any existing queue items
        $this->clearQueue($mix->id);

        // End any existing active sessions
        PlaybackSession::where('mix_id', $mix->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now()
            ]);

        // Create a new session
        $session = PlaybackSession::create([
            'mix_id' => $mix->id,
            'started_at' => now(),
            'is_active' => true
        ]);

        Log::info("Created new playback session {$session->id} for mix {$mix->id}");

        // Generate the queue entries
        $order = 0;
        foreach ($mix->songs as $song) {
            $order++;

            QueueSong::create([
                'song_id' => $song->id,
                'mix_id' => $mix->id,
                'user_id' => Auth::id() ?? $mix->user_id,
                'playback_session_id' => $session->id, // CHANGED FROM session_id
                'round_number' => 1,
                'order' => $order,
                'status' => 'pending',
                'is_killed' => false
            ]);
        }

        Log::info("Created {$order} queue entries for mix {$mix->id}");

        // If resetQueue is true, ensure the queue position is reset to 0
        if ($resetQueue) {
            Cache::put("mix_{$mix->id}_queue_position", 0, 3600);
            Log::info("Queue position reset for mix {$mix->id}");
        }

        return [
            'success' => true,
            'message' => "Queue initialized with {$order} songs"
        ];
    }

    /**
     * Start or resume queue playback
     */
    public function startPlayback(int $mixId, bool $resetQueue = false, ?string $deviceId = null): array
    {
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
        return $this->songPlaybackService->startPlayback($mixId, $deviceId);
    }

    /**
     * Stop playback and clear the queue
     */
    public function stopPlayback(int $mixId): array
    {
        $mix = Mix::findOrFail($mixId);
        $user = User::findOrFail($mix->user_id);

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
     * Add a song to the queue
     */
    public function addSongToQueue(int $mixId, int $songId, int $userId): QueueSong
    {
        Log::info("Adding song {$songId} to mix {$mixId} queue");

        // Get the active session
        $activeSession = PlaybackSession::where('mix_id', $mixId)
            ->where('is_active', true)
            ->first();

        if (!$activeSession) {
            // Create a session if one doesn't exist
            $activeSession = PlaybackSession::create([
                'mix_id' => $mixId,
                'started_at' => now(),
                'is_active' => true
            ]);
            Log::info("Created new playback session {$activeSession->id} for mix {$mixId} during addSongToQueue");
        }

        // Get highest order number in current round
        $maxOrder = QueueSong::where('mix_id', $mixId)
            ->where('round_number', 1)
            ->max('order') ?? 0;

        // Create queue entry
        $queueSong = QueueSong::create([
            'song_id' => $songId,
            'mix_id' => $mixId,
            'user_id' => $userId,
            'playback_session_id' => $activeSession->id, // CHANGED FROM session_id
            'round_number' => 1,
            'order' => $maxOrder + 1,
            'status' => 'pending',
            'is_killed' => false
        ]);

        return $queueSong;
    }

    /**
     * Remove a song from the queue (when pending)
     */
    public function removeSongFromQueue(int $queueSongId): bool
    {
        $queueSong = QueueSong::findOrFail($queueSongId);

        // Only allow removal if song is still pending
        if ($queueSong->status === 'pending') {
            Log::info("Removing song {$queueSong->song_id} from queue");
            return $queueSong->delete();
        }

        Log::warning("Cannot remove song {$queueSong->song_id} - status is {$queueSong->status}");
        return false;
    }

    /**
     * Kill a currently playing song (skip it)
     */
    public function killCurrentSong(int $mixId, ?int $userId = null): array
    {
        $currentSong = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->first();

        if (!$currentSong) {
            return [
                'success' => false,
                'message' => 'No song currently playing'
            ];
        }

        // Mark as killed
        $currentSong->update([
            'is_killed' => true,
            'status' => 'finished',
            'played_at' => Carbon::now()
        ]);

        if ($userId) {
            Log::info("User {$userId} killed song {$currentSong->song_id} in mix {$mixId}");
        } else {
            Log::info("System killed song {$currentSong->song_id} in mix {$mixId}");
        }

        // Advance to next song
        return $this->songPlaybackService->advanceToNextSong($mixId);
    }

    /**
     * Get the queue for a mix
     */
    public function getQueue(int $mixId): array
    {
        $playing = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->with(['song', 'user'])
            ->first();

        $pending = QueueSong::where('mix_id', $mixId)
            ->where('status', 'pending')
            ->where('is_killed', false)
            ->orderBy('round_number')
            ->orderBy('order')
            ->with(['song', 'user'])
            ->get();

        $finished = QueueSong::where('mix_id', $mixId)
            ->where('status', 'finished')
            ->orderBy('played_at', 'desc')
            ->with(['song', 'user'])
            ->limit(10)
            ->get();

        return [
            'playing' => $playing,
            'pending' => $pending,
            'history' => $finished
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
     * Record like for a queue song
     */
    public function likeSong(int $queueSongId, int $userId): array
    {
        $queueSong = QueueSong::findOrFail($queueSongId);
        $queueSong->increment('like_count');

        Log::info("User {$userId} liked song {$queueSong->song_id}");

        return [
            'success' => true,
            'like_count' => $queueSong->like_count
        ];
    }

    /**
     * Record dislike for a queue song
     */
    public function dislikeSong(int $queueSongId, int $userId): array
    {
        $queueSong = QueueSong::findOrFail($queueSongId);
        $queueSong->increment('dislike_count');

        // If dislikes reach threshold, kill the song
        if ($queueSong->status === 'playing' && $queueSong->dislike_count >= 3) {
            return $this->killCurrentSong($queueSong->mix_id, $userId);
        }

        Log::info("User {$userId} disliked song {$queueSong->song_id}");

        return [
            'success' => true,
            'dislike_count' => $queueSong->dislike_count
        ];
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
}
