<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Events\MixStatusChangedEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mix;
use App\Services\QueueManagementService;
use App\Services\SpotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\PlaybackDataUpdatedEvent;
use App\Jobs\PollSpotifyMixJob;
use App\Models\PlaybackSession;
use App\Services\PlaybackStateManager;

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

            if ($validated['active'] === true) {
                $resetQueue = $request->input('reset_queue', true);

                // IMPORTANT: Dispatch the polling job for active mixes
                PollSpotifyMixJob::dispatch($mix)
                    ->delay(now()->addSeconds(2));
                Log::info("Dispatched polling job for newly activated mix {$mix->id}");

                // Pass the deviceId to the dispatch function for playback
                dispatch(function () use ($mix, $resetQueue, $queueManagementService, $spotifyService, $deviceId) {
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
                            $queueManagementService->initializeQueue($mix, $resetQueue);

                            // Start playback after initialization is complete - PASS DEVICE ID!
                            $queueManagementService->startPlayback($mix->id, $resetQueue, $deviceId);

                            // Get current playback data to broadcast after a short delay
                            sleep(0.5);

                            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;

                            $playbackData = $spotifyService->getCurrentPlayback($user);

                            if ($playbackData) {
                                $playbackData['_timestamp'] = now()->timestamp;
                                $playbackData['is_initial_activation'] = true;

                                // Set cache for future polls
                                $cacheKey = "mix:playback:" . $mix->id;
                                Cache::put($cacheKey, $playbackData);

                                // Broadcast playback data
                                Log::info("Broadcasting initial playback data for newly activated mix {$mix->id}");
                                event(new PlaybackDataUpdatedEvent($mix, $playbackData));

                                // Set manual change flag using the local variable
                                $playbackState->setManualChange($mix);
                            } else {
                                $playbackState->setManualChange($mix);
                                Log::info("Unable to get immediate playback data for mix {$mix->id}, will rely on polling");
                            }

                            // After removing co-DJ
                            // Force a full state refresh
                            $playbackData = $spotifyService->getCurrentPlayback($mix->user);
                            if ($playbackData) {
                                $playbackData['_ownership_changed'] = true;
                                $playbackData['_timestamp'] = now()->timestamp;

                                // Update cache
                                $cacheKey = "mix:playback:" . $mix->id;
                                Cache::put($cacheKey, $playbackData);

                                // Force broadcast
                                event(new PlaybackDataUpdatedEvent($mix, $playbackData));
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

                // Get the playback state manager
                $playbackState = app(PlaybackStateManager::class);

                // Clear all states except device ID
                $playbackState->clearAllStates($mix);

                // End active sessions
                PlaybackSession::where('mix_id', $mix->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'ended_at' => now()
                    ]);

                // Broadcast deactivation event
                event(new MixStatusChangedEvent($mix, false));

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

    // Add this method to handle automatic deactivation when queue completes
    public function deactivateMixAfterQueueCompletion(Mix $mix)
    {
        // Update the mix status to inactive
        $mix->update(['is_active' => false]);

        // Log status change
        Log::info("Mix {$mix->id} automatically deactivated after queue completion");

        // First try to pause the current playback
        try {
            $user = $mix->co_dj_id ? $mix->coDj : $mix->user;
            $spotifyService = app(SpotifyService::class);
            $spotifyService->pausePlayback($user);
            Log::info("Paused Spotify playback during automatic mix deactivation for mix {$mix->id}");
        } catch (\Exception $e) {
            Log::error("Failed to pause playback during automatic deactivation: " . $e->getMessage());
            // Continue with deactivation even if pause fails
        }

        // Get the playback state manager
        $playbackState = app(PlaybackStateManager::class);

        // Clear all states except device ID
        $playbackState->clearAllStates($mix);

        // End active sessions
        PlaybackSession::where('mix_id', $mix->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'ended_at' => now()
            ]);

        // Broadcast deactivation event
        event(new MixStatusChangedEvent($mix, false));

        return response()->json([
            'success' => true,
            'status' => 'deactivated',
            'message' => 'Mix automatically deactivated after queue completion'
        ]);
    }
}
