<?php

namespace App\Services;

use App\Events\PlaybackDataUpdatedEvent;
use App\Models\QueueSong;
use App\Models\Mix;
use App\Models\User;
use App\Models\PlaybackSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Events\MixStatusChangedEvent;
use App\Events\DeviceUpdatedEvent;

class SongPlaybackService
{
    public function __construct(protected SpotifyService $spotifyService, protected PlaybackStateManager $playbackStateManager)
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
    public function startPlayback(Mix $mix, ?string $deviceId = null, ?string $spotifyTrackId = null): array
    {
        $mixId = $mix->id;

        // If no device ID was passed, check if one is stored in PlaybackStateManager
        if (!$deviceId) {
            $playbackState = app(PlaybackStateManager::class);
            $deviceId = $playbackState->getDeviceId($mix);

            if ($deviceId) {
                Log::info("Retrieved stored device ID {$deviceId} for mix {$mixId} from PlaybackStateManager");
            } else {
                // No device ID available, log for debugging
                Log::warning("No device ID available for mix {$mixId}, playback may fail");
            }
        }

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
        }
        // Add error handling - fixed to check boolean instead of array
        if (!$playResult) {
            Log::error("Failed to start playback for mix {$mixId}");
            $nextSong->update(['status' => 'pending', 'playback_session_id' => null]);

            // Use PlaybackStateManager:
            $this->playbackStateManager->set($mix, 'device_failure', true);
            event(new DeviceUpdatedEvent($mix));

            return [
                'success' => false,
                'message' => 'Failed to start playback'
            ];
        }

        // Clear device failure flag on success
        Cache::forget("mix:{$mixId}:device_failure");

        sleep(1.5);

        $playbackData = $this->spotifyService->getCurrentPlayback($user);

        Cache::put("mix:playback:" . $mix->id, $playbackData);

        event(new PlaybackDataUpdatedEvent($mix, $playbackData));

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
    public function advanceToNextSong(Mix $mix, ?string $deviceId = null): array
    {
        // If no device ID was passed, get it from PlaybackStateManager
        if (!$deviceId) {
            $deviceId = $this->playbackStateManager->getDeviceId($mix);
            if ($deviceId) {
                Log::info("Retrieved device ID {$deviceId} from PlaybackStateManager for mix {$mix->id}");
            }
        }

        // Get the active session
        $activeSession = PlaybackSession::where('mix_id', $mix->id)
            ->where('is_active', true)
            ->first();

        if (!$activeSession) {
            return [
                'success' => false,
                'message' => 'No active playback session'
            ];
        }

        // Get the currently playing song
        $currentSong = QueueSong::where('mix_id', $mix->id)
            ->where('status', 'playing')
            ->first();

        if ($currentSong) {
            // Mark as finished
            $currentSong->update([
                'status' => 'finished',
                'played_at' => Carbon::now()
            ]);

            Log::info("Marked song {$currentSong->id} as finished");

            // CRITICAL: Always check queue extension when skipping, not just every 3 songs
            // This ensures that rapidly skipping triggers extensions
            $pendingSongCount = QueueSong::where('mix_id', $mix->id)
                ->where('status', 'pending')
                ->count();

            // If we're getting low on songs, extend the queue BEFORE trying to get the next song
            if ($pendingSongCount <= 5) {
                $this->extendQueueIfNeeded($mix->id);
            }
        }

        // Set a flag indicating we're changing tracks to prevent false mismatch detection
        $playbackState = app(PlaybackStateManager::class);
        $playbackState->setDeviceChanged($mix);
        $playbackState->setPaused($mix, false);

        // Get and play the next song, passing the device ID
        $result = $this->startPlayback($mix, $deviceId);

        if (!$result['success'] && isset($result['message']) && $result['message'] === 'No songs in queue') {
            // Get the user for this mix
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

            // Make sure to mark the queue as completed in the database
            QueueSong::where('mix_id', $mix->id)
                ->whereIn('status', ['playing', 'pending'])
                ->update([
                    'status' => 'finished',
                    'played_at' => Carbon::now()
                ]);

            // Broadcast a queue completed event
            event(new MixStatusChangedEvent(
                $mix,
                true,  // Keep mix active but indicate queue completion
                'queue_completed'
            ));

            // Mark the active session as inactive since the queue is complete
            if ($activeSession) {
                $activeSession->update([
                    'is_active' => false,
                    'ended_at' => now()
                ]);
                Log::info("Marked playback session {$activeSession->id} as inactive after queue completion");
            }

            // Set a cache flag to indicate the queue is completed
            Cache::put("mix:{$mix->id}:queue_completed", true, now()->addMinutes(10));

            Log::info("Queue completed for mix {$mix->id}, all songs marked as finished");

            // Try to pause Spotify playback - WITH THE CORRECT USER
            try {
                // Use the previously defined $user variable
                $this->spotifyService->pausePlayback($user);
                Log::info("Paused playback after queue completion");
            } catch (\Exception $e) {
                Log::error("Failed to pause playback after queue completion: " . $e->getMessage());
            }

            return [
                'success' => false,
                'message' => 'Queue completed',
                'queue_completed' => true
            ];
        }

        return $result;
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
     * Attempt to resume the intended track with maximum device reliability
     */
    public function resumeIntendedTrack(int $mixId): array
    {
        $mix = Mix::findOrFail($mixId);

        $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

        // Try multiple ways to get device ID
        $deviceId = $this->playbackStateManager->getDeviceId($mix);

        if ($deviceId) {
            Log::info("Resuming intended track for mix {$mixId} with device ID {$deviceId}");
            $this->playbackStateManager->setDeviceChanged($mix);
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

            event(new DeviceUpdatedEvent($mix));

            $this->playbackStateManager->set($mix, 'device_failure', true);

            return [
                'success' => false,
                'message' => 'Failed to resume intended track'
            ];
        }

        Cache::forget("mix:{$mixId}:device_failure");

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

    public function prepareQueueForUserSwitch(int $mixId): void
    {
        // Only reset songs that are currently playing, NOT finished ones
        QueueSong::where('mix_id', $mixId)
            ->where('status', 'playing')
            ->update(['status' => 'pending']);

        // Important: don't touch 'finished' songs

        // Add debug log to trace ownership transition
        Log::info("Reset queue state for user switch on mix {$mixId} - only changed 'playing' to 'pending'");
    }

    /**
     * Checks if queue needs extension and extends it if necessary
     */
    public function checkAndExtendQueue(int $mixId): void
    {
        // Get the mix
        $mix = Mix::find($mixId);
        if (!$mix) {
            Log::warning("Cannot extend queue: Mix {$mixId} not found");
            return;
        }

        // Use the cached count if available to avoid redundant queries
        $pendingSongs = Cache::get("mix:{$mixId}:pending_count", null);

        // If not cached, get the count from the database
        if ($pendingSongs === null) {
            $pendingSongs = QueueSong::where('mix_id', $mixId)
                ->where('status', 'pending')
                ->count();

            // Cache the result
            Cache::put("mix:{$mixId}:pending_count", $pendingSongs, now()->addMinutes(1));
        }

        // Get batch size to determine threshold
        $batchSize = $mix->preset->batch_size;

        // If fewer than 1.5 batch sizes of songs remaining, add more rounds
        if ($pendingSongs <= ($batchSize * 1.5)) {
            Log::info("Queue for mix {$mixId} is running low ({$pendingSongs} songs left). Adding more rounds.");

            // Add 2 more rounds instead of just 1
            app(QueueManagementService::class)->appendRoundsToQueue($mix, 2);

            // Immediately recalculate and update the pending count after adding rounds
            $newPendingCount = QueueSong::where('mix_id', $mixId)
                ->where('status', 'pending')
                ->count();

            Cache::put("mix:{$mixId}:pending_count", $newPendingCount, now()->addMinutes(1));
        }
    }

    /**
     * Public wrapper to check and extend queue
     */
    public function extendQueueIfNeeded(int $mixId): bool
    {
        // Add debug logging to trace execution
        Log::info("Checking if queue needs extension for mix {$mixId}");

        // Check pending song count
        $pendingSongs = QueueSong::where('mix_id', $mixId)
            ->where('status', 'pending')
            ->count();

        Log::info("Mix {$mixId} has {$pendingSongs} pending songs left");

        // Get the mix
        $mix = Mix::find($mixId);
        if (!$mix) {
            Log::warning("Cannot extend queue: Mix {$mixId} not found");
            return false;
        }

        // Get batch size to determine threshold
        $batchSize = $mix->preset->batch_size;

        // IMPORTANT: Use a much higher threshold when fewer songs remain
        // If we only have 3 or fewer songs OR less than threshold, extend
        if ($pendingSongs <= 3 || $pendingSongs <= ($batchSize * 0.5)) {
            Log::info("Queue for mix {$mixId} is running low ({$pendingSongs} songs left). Adding more rounds.");

            // Force add 2 more rounds
            $result = app(QueueManagementService::class)->appendRoundsToQueue($mix, 2);

            if (isset($result['success']) && $result['success']) {
                // Force update the cache after extending
                $newCount = QueueSong::where('mix_id', $mixId)
                    ->where('status', 'pending')
                    ->count();

                Log::info("Successfully extended queue for mix {$mixId}. Now has {$newCount} pending songs");
                Cache::put("mix:{$mixId}:pending_count", $newCount, now()->addMinutes(5));
                return true;
            } else {
                Log::warning("Failed to extend queue for mix {$mixId}");
                return false;
            }
        }

        Log::info("Queue extension not needed for mix {$mixId} ({$pendingSongs} pending songs)");
        return false;
    }

}
