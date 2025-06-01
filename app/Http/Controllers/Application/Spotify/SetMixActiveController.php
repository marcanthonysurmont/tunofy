<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Events\MixStatusChangedEvent;
use App\Events\StatUpdatedEvent;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Mix;
use App\Services\Queue\QueueManagementService;
use App\Services\Spotify\SpotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;
use App\Jobs\PollSpotifyMixJob;
use App\Models\PlaybackSession;
use App\Services\Playback\PlaybackStateManager;
use App\Models\GlobalUserStat;
use Illuminate\Support\Facades\DB;
use App\Models\MixStat;

class SetMixActiveController extends Controller
{
    public function __invoke(
        Request $request,
        Mix $mix,
        QueueManagementService $queueManagementService,
        SpotifyService $spotifyService,
        PlaybackStateManager $playbackStateManager
    ): JsonResponse {
        try {
            // Get the deviceId from the request
            $deviceId = $request->input('deviceId');

            // Validate request
            $validated = $request->validate([
                'active' => 'required|boolean',
                'reset_queue' => 'sometimes|boolean',
            ]);

            // When activating a mix, check for conflicts FIRST
            if ($validated['active'] === true) {
                // Get the controlling user (owner or co-dj)

                // Find any active mixes for this user
                $activeConflictingMixes = Mix::conflictingActiveMixes($mix->id)->get();

                // If there are active mixes, prevent activation and return error
                if ($activeConflictingMixes->isNotEmpty()) {
                    $conflictingMix = $activeConflictingMixes->first();

                    return response()->json([
                        'success' => false,
                        'message' => "You already have an active mix with '{$conflictingMix->name}'.",
                    ], 400);
                }
            }

            // Log the device ID
            if ($deviceId) {
                Log::info("SetMixActiveController received deviceId: " . $deviceId);
                // Store it even before queue initialization
                $playbackStateManager->setDeviceId($mix, $deviceId);
            }

            // Update the mix status
            $mix->update(['is_active' => $validated['active']]);

            // Log status change
            Log::info($validated['active'] ? "Mix {$mix->id} activated" : "Mix {$mix->id} deactivated");

            $otherMixes = Mix::otherMixesForUser($mix->id)->get();
            $changeReason = 'other_mix';

            foreach ($otherMixes as $otherMix) {
                event(new MixStatusChangedEvent($otherMix, $otherMix->is_active, $changeReason));
            }

            if ($validated['active'] === true) {
                // IMPORTANT: Dispatch the polling job for active mixes
                PollSpotifyMixJob::dispatch($mix)
                    ->delay(now()->addSeconds(2));
                Log::info("Dispatched polling job for newly activated mix {$mix->id}");

                // Pass the deviceId to the dispatch function for playback
                dispatch(function () use ($mix, $queueManagementService, $deviceId) {
                    $lock = Cache::lock("mix:{$mix->id}:state_change", 10);

                    try {
                        if ($lock->get()) {
                            // Get the playback state manager
                            $playbackState = app(PlaybackStateManager::class);

                            // Clear key flags
                            $playbackState->setPaused($mix, false);
                            $playbackState->setQueueCompleted($mix, false);
                            $playbackState->setManualChange($mix);

                            // Initialize queue first
                            $queueManagementService->initializeQueue($mix);

                            // Get the first song to play
                            $firstSong = $mix->queueSongs()
                                ->where('status', 'pending')
                                ->orderBy('order')
                                ->with('song')
                                ->first();

                            if ($firstSong) {
                                // Mark the song as playing
                                $firstSong->update(['status' => 'playing']);

                                // Send an intermediate "loading" state if you want (optional)
                                $loadingData = [
                                    'is_playing' => true,
                                    'is_loading' => true,  // Add this flag for your loading indicator
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
                                    '_action' => 'activating',  // Different action for loading state
                                ];

                                $playbackState->setPlaybackData($mix, $loadingData);
                                Log::info("Broadcasting loading state for mix {$mix->id}");
                                event(new PlaybackDataUpdatedEvent($mix, $loadingData));

                                // Start playback (this is the slow operation)
                                $queueManagementService->startPlayback($mix->id, $deviceId);

                                // AFTER playback has started, send the actual playback data
                                $playbackData = [
                                    'is_playing' => true,
                                    'is_loading' => false,  // Clear loading flag
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

                                $playbackState->setPlaybackData($mix, $playbackData);
                                Log::info("Broadcasting actual playback data for mix {$mix->id} after playback started");
                                event(new PlaybackDataUpdatedEvent($mix, $playbackData));

                                // Flag manual change to prevent polling override
                                $playbackState->setManualChange($mix);
                            } else {
                                // No songs in queue
                                Log::info("No songs in queue for mix {$mix->id}");

                                // Create empty playback data
                                $emptyPlaybackData = [
                                    'is_playing' => false,
                                    '_timestamp' => now()->timestamp,
                                    '_action' => 'activate',
                                    'queue_empty' => true,
                                    'is_initial_activation' => true
                                ];

                                // Set cache and broadcast
                                $playbackState->setPlaybackData($mix, $emptyPlaybackData);
                                event(new PlaybackDataUpdatedEvent($mix, $emptyPlaybackData));

                                // Don't try to start playback if queue is empty
                            }
                        }
                        // Make sure to release the lock when done
                        $lock->release();
                    } catch (\Exception $e) {
                        if (isset($lock)) {
                            $lock->release();
                        }
                        throw $e;
                    }
                })->afterResponse();

                // Return quickly with success to update UI
                return response()->json([
                    'activation' => [
                        'success' => true
                    ],
                    'status' => 'activating',
                    'message' => 'Playback starting...'
                ]);
            } else {
                // First try to pause the current playback
                try {
                    $user = $mix->co_dj_id ? $mix->coDj : $mix->user;
                    $spotifyService->pausePlayback($user);
                    Log::info("Paused Spotify playback during mix deactivation for mix {$mix->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to pause playback during deactivation: " . $e->getMessage());
                    // Continue with deactivation even if pause fails
                }

                // Clear all states except device ID
                $playbackStateManager->clearAllStates($mix);

                // End active sessions
                PlaybackSession::where('mix_id', $mix->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'ended_at' => now()
                    ]);

                // Broadcast deactivation event
                event(new MixStatusChangedEvent($mix, false));

                $playbackData = $spotifyService->getCurrentPlayback($mix->user);

                MixStat::updateOrCreate(
                    ['mix_id' => $mix->id],
                    [
                        'songs_played' => DB::raw('songs_played + 1'),
                        'minutes_played' => DB::raw('minutes_played + ' . $playbackData['progress_ms'] / 60000),
                    ]
                );

                StatUpdatedEvent::dispatch($mix);

                GlobalUserStat::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                    ],
                    [
                        'mixes_played' => DB::raw('mixes_played + 1'),
                    ],
                );

                return response()->json([
                    'success' => true,
                    'status' => 'deactivated'
                ]);
            }
        } catch (\Exception $e) {
            // Log the complete exception details
            Log::error("CRITICAL ERROR in SetMixActiveController: " . $e->getMessage());
            Log::error("Exception type: " . get_class($e));
            Log::error("Stack trace: " . $e->getTraceAsString());

            // Return a clear error response
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'type' => get_class($e)
            ], 500);
        }
    }
}
