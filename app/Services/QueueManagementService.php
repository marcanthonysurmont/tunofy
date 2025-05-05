<?php

namespace App\Services;

use App\Models\Mix;
use App\Models\QueueSong;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
    {}

    /**
     * Initialize queue for a mix
     *
     * Creates queue entries for all songs in the mix.
     */
    public function initializeQueue(Mix $mix): array
    {
        Log::info("Initializing queue for mix {$mix->id}");

        // Clean any existing queue items
        $this->clearQueue($mix->id);

        // Generate the queue entries
        $order = 0;
        foreach ($mix->songs as $song) {
            $order++;

            QueueSong::create([
                'song_id' => $song->id,
                'mix_id' => $mix->id,
                'user_id' => Auth::id() ?? $mix->user_id,
                'round_number' => 1,
                'order' => $order,
                'status' => 'pending',
                'is_killed' => false
            ]);
        }

        Log::info("Created {$order} queue entries for mix {$mix->id}");

        return [
            'success' => true,
            'message' => "Queue initialized with {$order} songs"
        ];
    }

    /**
     * Start or resume queue playback
     */
    public function startPlayback(int $mixId): array
    {
        Log::info("Starting playback for mix {$mixId}");

        // Get the mix and user
        $mix = Mix::findOrFail($mixId);
        $user = User::findOrFail(Auth::id() ?? $mix->user_id);

        // Before playing the song, ensure a device is active
        $this->spotifyService->activateDevice($user);

        // Give Spotify a moment to register the device activation
        sleep(1);

        return $this->songPlaybackService->startPlayback($mixId);
    }

    /**
     * Stop playback and clear the queue
     */
    public function stopPlayback(int $mixId): array
    {
        Log::info("Stopping playback for mix {$mixId}");

        // Clear the queue
        $this->clearQueue($mixId);

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

        // Get highest order number in current round
        $maxOrder = QueueSong::where('mix_id', $mixId)
            ->where('round_number', 1)
            ->max('order') ?? 0;

        // Create queue entry
        $queueSong = QueueSong::create([
            'song_id' => $songId,
            'mix_id' => $mixId,
            'user_id' => $userId,
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
}
