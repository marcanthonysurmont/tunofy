<?php

namespace App\Services;

use App\Models\QueueSong;
use App\Models\Mix;
use App\Models\User;
use App\Models\PlaybackSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SongPlaybackService
{
    public function __construct(protected SpotifyService $spotifyService)
    {
    }

    /**
     * Get the next song to play from the queue
     */
    public function getNextSongToPlay(int $mixId): ?QueueSong
    {
        return QueueSong::where('mix_id', $mixId)
            ->where('status', 'pending')
            ->where('is_killed', false)
            ->orderBy('round_number')
            ->orderBy('order')
            ->with('song')
            ->first();
    }

    /**
     * Start playback of the next song in the queue
     */
    public function startPlayback(int $mixId, ?string $deviceId = null): array
    {
        // Get the mix
        $mix = Mix::findOrFail($mixId);

        // Determine which user to use for playback
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        // Get the active session for this mix
        $activeSession = PlaybackSession::where('mix_id', $mixId)
            ->where('is_active', true)
            ->first();

        if (!$activeSession) {
            // Create a new session if none exists
            $activeSession = PlaybackSession::create([
                'mix_id' => $mixId,
                'started_at' => now(),
                'is_active' => true
            ]);
            Log::info("Created new playback session {$activeSession->id} for mix {$mixId}");
        }

        // Check if there's a song currently playing
        $currentlyPlaying = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->first();

        if ($currentlyPlaying) {
            // If this song doesn't have a session, associate it now
            if (!$currentlyPlaying->playback_session_id) {
                $currentlyPlaying->update(['playback_session_id' => $activeSession->id]);
                Log::info("Associated already playing song {$currentlyPlaying->id} with session {$activeSession->id}");
            }

            // Load relationship if not loaded
            if (!$currentlyPlaying->relationLoaded('song')) {
                $currentlyPlaying->load('song');
            }

            return [
                'success' => true,
                'queue_song' => $currentlyPlaying,
                'song' => $currentlyPlaying->song
            ];
        }

        // Get the next song to play
        $nextSong = $this->getNextSongToPlay($mixId);
        if (!$nextSong) {
            return [
                'success' => false,
                'message' => 'No songs in queue'
            ];
        }

        // Associate with session and mark as playing
        $nextSong->update([
            'playback_session_id' => $activeSession->id,
            'status' => 'playing'
        ]);

        // Use the direct playback method if device is specified
        $playResult = false;
        if ($deviceId) {
            $playResult = $this->spotifyService->playTrackOnDevice($user, $nextSong->song->spotify_id, $deviceId);
        } else {
            $playResult = $this->spotifyService->playSong($user, $nextSong->song->spotify_id);
        }

        // Add error handling - fixed to check boolean instead of array
        if (!$playResult) {
            Log::error("Failed to start playback for mix {$mixId}");
            $nextSong->update(['status' => 'pending', 'playback_session_id' => null]);
            return [
                'success' => false,
                'message' => 'Failed to start playback'
            ];
        }

        $playbackData = $this->spotifyService->getCurrentPlayback($user);

        if (!$playbackData) {
            Log::error("Failed to get current playback data for mix {$mixId}");
            $nextSong->update(['status' => 'pending', 'playback_session_id' => null]);
            return [
                'success' => false,
                'message' => 'Failed to get current playback'
            ];
        }

        Log::info("Started playing song ID: {$nextSong->id} for mix {$mixId} in session {$activeSession->id}");
        return [
            'success' => true,
            'queue_song' => $nextSong,
            'song' => $nextSong->song
        ];
    }

    /**
     * Advance to the next song in the queue
     */
    public function advanceToNextSong(int $mixId): array
    {
        $mix = Mix::findOrFail($mixId);

        // Retrieve the cached device ID if present
        $deviceId = Cache::get("mix:{$mix->id}:device_id");

        // Get the active session
        $activeSession = PlaybackSession::where('mix_id', $mixId)
            ->where('is_active', true)
            ->first();

        if (!$activeSession) {
            return [
                'success' => false,
                'message' => 'No active playback session'
            ];
        }

        // Get the currently playing song
        $currentSong = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->first();

        if ($currentSong) {
            // Mark as finished
            $currentSong->update([
                'status' => 'finished',
                'played_at' => Carbon::now()
            ]);

            Log::info("Marked song {$currentSong->id} as finished");
        }

        // Set a flag indicating we're changing tracks to prevent false mismatch detection
        Cache::put("mix:{$mixId}:device_changed", true, now()->addSeconds(5));

        // IMPORTANT: Clear the paused flag to ensure polling resumes
        Cache::forget("mix:{$mixId}:paused");

        // Get and play the next song, passing the device ID
        return $this->startPlayback($mixId, $deviceId);
    }

    public function returnToPreviousSong(int $mixId): array
    {
        $mix = Mix::findOrFail($mixId);
        
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;


        // Retrieve the cached device ID if present
        $deviceId = Cache::get("mix:{$mix->id}:device_id");

        // Get the currently playing song
        $currentSong = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->first();

        if (!$currentSong) {
            return [
                'success' => false,
                'message' => 'No song is currently playing'
            ];
        }

        // Find the active session for this mix
        $activeSession = PlaybackSession::where('mix_id', $mixId)
            ->where('is_active', true)
            ->first();

        if (!$activeSession) {
            return [
                'success' => false,
                'message' => 'No active playback session found'
            ];
        }

        // Find the previous song in the SAME SESSION
        $previousSong = QueueSong::where('mix_id', $mixId)
            ->where('playback_session_id', $activeSession->id)
            ->where('status', 'finished')
            ->where('order', '<', $currentSong->order)
            ->orderBy('order', 'desc')
            ->first();

        if (!$previousSong) {
            return [
                'success' => false,
                'message' => 'Already at the first song in this session'
            ];
        }

        // Mark current song as pending
        $currentSong->update([
            'status' => 'pending',
        ]);
        Log::info("Marked current song {$currentSong->id} as pending for previous song operation");

        // Mark previous song as playing
        $previousSong->update([
            'status' => 'playing',
        ]);
        Log::info("Marked song {$previousSong->id} as playing (previous song) [order: {$previousSong->order}]");

        // Load the song relationship if needed
        if (!$previousSong->relationLoaded('song')) {
            $previousSong->load('song');
        }

        // Set a flag to indicate we're changing tracks to prevent false mismatch detection
        Cache::put("mix:{$mixId}:device_changed", true, now()->addSeconds(5));

        // Explicitly play this song on Spotify with the same device ID
        $playResult = false;
        if ($deviceId) {
            $playResult = $this->spotifyService->playTrackOnDevice($user, $previousSong->song->spotify_id, $deviceId);
        } else {
            $playResult = $this->spotifyService->playSong($user, $previousSong->song->spotify_id);
        }

        // IMPORTANT: Clear the paused flag to ensure polling resumes
        Cache::forget("mix:{$mixId}:paused");

        if (!$playResult) {
            // Revert the status changes if we failed to play
            $currentSong->update(['status' => 'playing']);
            $previousSong->update(['status' => 'finished']);

            return [
                'success' => false,
                'message' => 'Failed to play previous song on Spotify'
            ];
        }

        return [
            'success' => true,
            'queue_song' => $previousSong,
            'song' => $previousSong->song
        ];
    }

    /**
     * Get the currently playing song for a mix
     */
    public function getCurrentlyPlayingSong(int $mixId): ?QueueSong
    {
        return QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->with('song')
            ->first();
    }

    /**
     * Check if a mix has pending songs
     */
    public function hasPendingSongs(int $mixId): bool
    {
        return QueueSong::where('mix_id', $mixId)
            ->where('status', 'pending')
            ->where('is_killed', false)
            ->exists();
    }

    /**
     * Play a specific song on Spotify
     */
    private function playSongOnSpotify(User $user, QueueSong $queueSong): array
    {
        try {
            $songUri = "spotify:track:{$queueSong->song->spotify_id}";
            $result = $this->spotifyService->playSong($user, $songUri);

            if (!$result) {
                Log::error("Failed to play song {$queueSong->song->spotify_id} on Spotify");
                return [
                    'success' => false,
                    'message' => 'Failed to play song on Spotify'
                ];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error("Error playing song on Spotify: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error playing song: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mark all queue songs for a mix as finished
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
     * Verify that the expected song is actually playing
     */
    public function verifyPlaybackIntegrity(int $mixId, array $playbackData): bool
    {
        // Get what we think is playing
        $currentQueueSong = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->with('song')
            ->first();

        if (!$currentQueueSong) {
            return true; // Nothing playing in our system, so no mismatch
        }

        // Get what's actually playing on Spotify
        $spotifyTrackUri = $playbackData['item']['uri'] ?? null;
        $expectedTrackUri = "spotify:track:" . $currentQueueSong->song->spotify_id;

        // Compare the two
        if ($spotifyTrackUri && $spotifyTrackUri !== $expectedTrackUri) {
            Log::warning("Playback mismatch detected: Expected {$expectedTrackUri}, playing {$spotifyTrackUri}");

            // Update the queue to reflect reality
            $currentQueueSong->update([
                'status' => 'interrupted',
                'played_at' => Carbon::now()
            ]);

            return false;
        }

        return true;
    }

    /**
     * Attempt to resume the intended track with maximum device reliability
     */
    public function resumeIntendedTrack(int $mixId): array
    {
        $mix = Mix::findOrFail($mixId);
        
        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        // Try multiple ways to get device ID
        $deviceId = Cache::get("mix:{$mix->id}:device_id");

        if ($deviceId) {
            Log::info("Resuming intended track for mix {$mixId} with device ID {$deviceId}");

            // Set a flag to indicate we're using a specific device
            Cache::put("mix:{$mix->id}:device_changed", true, now()->addSeconds(10));
        } else {
            Log::info("Resuming intended track for mix {$mixId} with no specific device");
        }

        // Get what we think should be playing
        $currentQueueSong = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->with('song')
            ->first();

        if (!$currentQueueSong) {
            return [
                'success' => false,
                'message' => 'No song currently marked as playing in the mix'
            ];
        }

        // Force the next song to play on the correct device
        $playResult = false;
        if ($deviceId) {
            $playResult = $this->spotifyService->playTrackOnDevice($user, $currentQueueSong->song->spotify_id, $deviceId);
        } else {
            $playResult = $this->spotifyService->playSong($user, $currentQueueSong->song->spotify_id);
        }

        if (!$playResult) {
            Log::error("Failed to resume intended track {$currentQueueSong->song->spotify_id}");
            return [
                'success' => false,
                'message' => 'Failed to resume intended track'
            ];
        }

        // Cache the device ID again to ensure persistence
        if ($deviceId) {
            Cache::put("mix:{$mixId}:device_id", $deviceId, now()->addHours(1));
        }

        Log::info("Successfully resumed intended track {$currentQueueSong->song->spotify_id}" . ($deviceId ? " on device {$deviceId}" : ""));
        return [
            'success' => true,
            'queue_song' => $currentQueueSong,
            'song' => $currentQueueSong->song
        ];
    }
}
