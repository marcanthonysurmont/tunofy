<?php

namespace App\Services\Queue;

use App\Events\MixStatusChangedEvent;
use App\Events\PlaybackDataUpdatedEvent;
use App\Events\StatUpdatedEvent;
use App\Jobs\PollSpotifyMixJob;
use App\Models\GlobalUserStat;
use App\Models\Mix;
use App\Models\MixStat;
use App\Models\PlaybackSession;
use App\Services\Playback\PlaybackStateManager;
use App\Services\Spotify\SpotifyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\Queue\QueueManagementService;

class MixActivationService
{
    public function __construct(
        protected QueueManagementService $queueService,
        protected SpotifyService $spotifyService,
        protected PlaybackStateManager $playbackStateManager
    ) {
    }

    /**
     * Check for conflicting active mixes
     */
    public function checkConflictingMixes(Mix $mix): ?Mix
    {
        $activeConflictingMixes = Mix::conflictingActiveMixes($mix->id)->get();
        return $activeConflictingMixes->isNotEmpty() ? $activeConflictingMixes->first() : null;
    }

    /**
     * Toggle a mix active state with consolidated handling of related operations
     */
    public function toggleMixActive(Mix $mix, bool $activate, ?string $deviceId = null): array
    {
        if ($activate) {
            return $this->activateMix($mix, $deviceId);
        } else {
            return $this->deactivateMix($mix);
        }
    }

    /**
     * Activate a mix and handle all related operations in one place
     */
    public function activateMix(Mix $mix, ?string $deviceId = null, bool $resetQueue = false): array
    {
        $lock = Cache::lock("mix:{$mix->id}:state_change", 10);

        try {
            if (!$lock->get()) {
                return [
                    'success' => false,
                    'message' => 'Mix state is currently changing. Please try again.',
                    'locked' => true
                ];
            }

            // Update the mix status
            $mix->update(['is_active' => true]);

            // Log status change
            Log::info("Mix {$mix->id} activated");

            // Update device ID if provided
            if ($deviceId) {
                $this->playbackStateManager->setDeviceId($mix, $deviceId);
            }

            // IMPORTANT: Dispatch the polling job for active mixes
            PollSpotifyMixJob::dispatch($mix)
                ->delay(now()->addSeconds(2));

            // Initialize queue and start playback in background
            dispatch(function () use ($mix, $deviceId) {
                $this->initializeQueueAndStartPlayback($mix, $deviceId);
            })->afterResponse();

            // Return quickly with success
            return [
                'activation' => [
                    'success' => true
                ],
                'status' => 'activating',
                'message' => 'Playback starting...'
            ];
        } finally {
            // Always release the lock - without checking owned()
            if (isset($lock)) {
                $lock->release();
            }
        }
    }

    /**
     * Initialize queue and start playback (called from background job)
     */
    protected function initializeQueueAndStartPlayback(Mix $mix, ?string $deviceId = null): void
    {
        // Additional locking to prevent concurrent queue initialization
        $lock = Cache::lock("mix:{$mix->id}:queue_init", 30);

        try {
            if ($lock->get()) {
                // Clear key flags
                $this->playbackStateManager->setPaused($mix, false);
                $this->playbackStateManager->setQueueCompleted($mix, false);
                $this->playbackStateManager->setManualChange($mix);

                // Initialize queue
                $this->queueService->initializeQueue($mix);

                // Get the first song to play
                $firstSong = $mix->queueSongs()
                    ->where('status', 'pending')
                    ->orderBy('order')
                    ->with('song')
                    ->first();

                if ($firstSong) {
                    // Mark the song as playing
                    $firstSong->update(['status' => 'playing']);

                    // Send loading state
                    $loadingData = [
                        'is_playing' => true,
                        'is_loading' => true,
                        'item' => [
                            'id' => $firstSong->song->spotify_id,
                            'name' => $firstSong->song->name,
                            'duration_ms' => $firstSong->song->duration_ms,
                            'artists' => [['name' => $firstSong->song->artist]],
                            'album' => [
                                'images' => [['url' => $firstSong->song->image_url]]
                            ]
                        ],
                        '_timestamp' => now()->timestamp,
                        '_action' => 'activating'
                    ];

                    $this->playbackStateManager->setPlaybackData($mix, $loadingData);
                    event(new PlaybackDataUpdatedEvent($mix, $loadingData));

                    // Start playback
                    $this->queueService->startPlayback($mix, $deviceId);

                    // Send final playback data
                    $playbackData = [
                        'is_playing' => true,
                        'is_loading' => false,
                        'item' => [
                            'id' => $firstSong->song->spotify_id,
                            'name' => $firstSong->song->name,
                            'duration_ms' => $firstSong->song->duration_ms,
                            'artists' => [['name' => $firstSong->song->artist]],
                            'album' => [
                                'images' => [['url' => $firstSong->song->image_url]]
                            ]
                        ],
                        '_timestamp' => now()->timestamp,
                        '_action' => 'activate',
                        'playback_started' => true
                    ];

                    $this->playbackStateManager->setPlaybackData($mix, $playbackData);
                    event(new PlaybackDataUpdatedEvent($mix, $playbackData));
                    event(new MixStatusChangedEvent($mix, true));

                    $this->playbackStateManager->setManualChange($mix);
                } else {
                    // No songs in queue
                    $emptyPlaybackData = [
                        'is_playing' => false,
                        '_timestamp' => now()->timestamp,
                        '_action' => 'activate',
                        'queue_empty' => true,
                        'is_initial_activation' => true
                    ];

                    $this->playbackStateManager->setPlaybackData($mix, $emptyPlaybackData);
                    event(new PlaybackDataUpdatedEvent($mix, $emptyPlaybackData));
                }
            }
        } finally {
            // Always release the lock - without checking owned()
            if (isset($lock)) {
                $lock->release();
            }
        }
    }

    /**
     * Deactivate a mix and handle all related cleanup
     */
    public function deactivateMix(Mix $mix): array
    {
        // Try to pause playback
        $this->tryPausePlayback($mix);

        // Update mix status
        $mix->update(['is_active' => false]);
        Log::info("Mix {$mix->id} deactivated");

        // Clear states and end sessions
        $this->playbackStateManager->clearAllStates($mix);
        $this->endActiveSessions($mix);

        // Broadcast event
        event(new MixStatusChangedEvent($mix, false));

        // Update stats
        $this->updateDeactivationStats($mix);

        return [
            'success' => true,
            'status' => 'deactivated'
        ];
    }

    /**
     * Notify other mixes about status changes
     */
    protected function notifyOtherMixes(Mix $mix): void
    {
        $otherMixes = Mix::otherMixesForUser($mix->id)->get();
        foreach ($otherMixes as $otherMix) {
            event(new MixStatusChangedEvent($otherMix, $otherMix->is_active, 'other_mix'));
        }
    }

    /**
     * Initialize playback after activation
     */
    protected function initializePlayback(Mix $mix, ?string $deviceId): void
    {
        dispatch(function () use ($mix, $deviceId) {
            $lock = Cache::lock("mix:{$mix->id}:state_change", 10);

            try {
                if (!$lock->get()) {
                    Log::warning("Could not acquire lock for mix {$mix->id}");
                    return;
                }

                // Clear flags
                $this->playbackStateManager->setPaused($mix, false);
                $this->playbackStateManager->setQueueCompleted($mix, false);
                $this->playbackStateManager->setManualChange($mix);

                // Initialize queue
                $this->queueService->initializeQueue($mix);

                // Get first song
                $firstSong = $mix->queueSongs()
                    ->where('status', 'pending')
                    ->orderBy('order')
                    ->with('song')
                    ->first();

                if ($firstSong) {
                    $this->startFirstSong($mix, $firstSong, $deviceId);
                } else {
                    $this->handleEmptyQueue($mix);
                }

            } catch (Exception $e) {
                Log::error("Error initializing playback: " . $e->getMessage());
            } finally {
                $lock?->release();
            }
        })->afterResponse();
    }

    /**
     * Start playing the first song in the queue
     */
    protected function startFirstSong(Mix $mix, $firstSong, ?string $deviceId): void
    {
        // Mark song as playing
        $firstSong->update(['status' => 'playing']);

        // Send loading state
        $loadingData = $this->createPlaybackData($firstSong, true, 'activating');
        $this->playbackStateManager->setPlaybackData($mix, $loadingData);
        event(new PlaybackDataUpdatedEvent($mix, $loadingData));

        // Start playback
        $this->queueService->startPlayback($mix, $deviceId);

        // Send final playback data
        $playbackData = $this->createPlaybackData($firstSong, false, 'activate', true);
        $this->playbackStateManager->setPlaybackData($mix, $playbackData);
        event(new PlaybackDataUpdatedEvent($mix, $playbackData));
        event(new MixStatusChangedEvent($mix, true));

        // Set manual flag
        $this->playbackStateManager->setManualChange($mix);
    }

    /**
     * Handle case where queue is empty
     */
    protected function handleEmptyQueue(Mix $mix): void
    {
        Log::info("No songs in queue for mix {$mix->id}");

        $emptyPlaybackData = [
            'is_playing' => false,
            '_timestamp' => now()->timestamp,
            '_action' => 'activate',
            'queue_empty' => true,
            'is_initial_activation' => true
        ];

        $this->playbackStateManager->setPlaybackData($mix, $emptyPlaybackData);
        event(new PlaybackDataUpdatedEvent($mix, $emptyPlaybackData));
    }

    /**
     * Create playback data array from song
     */
    protected function createPlaybackData($song, bool $isLoading, string $action, bool $playbackStarted = false): array
    {
        return [
            'is_playing' => true,
            'is_loading' => $isLoading,
            'item' => [
                'id' => $song->song->spotify_id,
                'name' => $song->song->name,
                'duration_ms' => $song->song->duration_ms,
                'artists' => [['name' => $song->song->artist]],
                'album' => [
                    'images' => [['url' => $song->song->image_url]]
                ]
            ],
            '_timestamp' => now()->timestamp,
            '_action' => $action,
            'playback_started' => $playbackStarted
        ];
    }

    /**
     * Try to pause playback
     */
    protected function tryPausePlayback(Mix $mix): void
    {
        try {
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;
            $this->spotifyService->pausePlayback($user);
            Log::info("Paused Spotify playback during mix deactivation for mix {$mix->id}");
        } catch (Exception $e) {
            Log::error("Failed to pause playback during deactivation: " . $e->getMessage());
        }
    }

    /**
     * End active playback sessions
     */
    protected function endActiveSessions(Mix $mix): void
    {
        PlaybackSession::where('mix_id', $mix->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now()
            ]);
    }

    /**
     * Update stats after deactivation
     */
    protected function updateDeactivationStats(Mix $mix): void
    {
        $playbackData = $this->spotifyService->getCurrentPlayback($mix->user);

        if ($playbackData !== null && isset($playbackData['progress_ms'])) {
            MixStat::updateOrCreate(
                ['mix_id' => $mix->id],
                [
                    'songs_played' => DB::raw('songs_played + 1'),
                    'minutes_played' => DB::raw('minutes_played + ' . $playbackData['progress_ms'] / 60000),
                ]
            );
        } else {
            MixStat::updateOrCreate(
                ['mix_id' => $mix->id],
                [
                    'songs_played' => DB::raw('songs_played + 1'),
                ]
            );
        }

        StatUpdatedEvent::dispatch($mix);

        GlobalUserStat::updateOrCreate(
            ['user_id' => Auth::id()],
            ['mixes_played' => DB::raw('mixes_played + 1')]
        );
    }
}
