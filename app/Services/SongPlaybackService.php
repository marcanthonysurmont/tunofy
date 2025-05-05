<?php

namespace App\Services;

use App\Models\QueueSong;
use App\Models\Mix;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
     * Start playback for a mix (initial or next song)
     */
    public function startPlayback(int $mixId): array
    {
        $mix = Mix::findOrFail($mixId);
        $user = User::findOrFail($mix->user_id);

        // Check if there's a song currently playing
        $currentlyPlaying = QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->with('song')
            ->first();

        // If a song is already playing, just return it
        if ($currentlyPlaying) {
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

        // Mark as playing and play on Spotify
        $nextSong->update(['status' => 'playing']);
        $playResult = $this->playSongOnSpotify($user, $nextSong);

        if (!$playResult['success']) {
            $nextSong->update(['status' => 'pending']);
            return $playResult;
        }

        Log::info("Started playing song ID: {$nextSong->id} for mix {$mixId}");
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
        $user = User::findOrFail($mix->user_id);

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

        // Get and play the next song
        return $this->startPlayback($mixId);
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
     * Attempt to resume the currently intended track
     * (useful when Spotify gets out of sync with the app's queue)
     */
    public function resumeIntendedTrack(int $mixId): array
    {
        $mix = Mix::findOrFail($mixId);
        $user = User::findOrFail($mix->user_id);

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

        // Attempt to play the correct song
        $songUri = "spotify:track:{$currentQueueSong->song->spotify_id}";
        $playResult = $this->spotifyService->playSong($user, $songUri);

        if (!$playResult) {
            Log::error("Failed to resume intended track {$currentQueueSong->song->spotify_id}");
            return [
                'success' => false,
                'message' => 'Failed to resume intended track'
            ];
        }

        Log::info("Successfully resumed intended track {$currentQueueSong->song->spotify_id}");
        return [
            'success' => true,
            'queue_song' => $currentQueueSong,
            'song' => $currentQueueSong->song
        ];
    }
}
