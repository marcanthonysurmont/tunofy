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

class SetMixActiveController extends Controller
{
    public function __invoke(
        Request $request,
        Mix $mix,
        QueueManagementService $queueManagementService,
        SpotifyService $spotifyService
    ): JsonResponse {
        // Get the deviceId from the request
        $deviceId = $request->input('deviceId');

        // Validate request
        $validated = $request->validate([
            'active' => 'required|boolean',
            'reset_queue' => 'sometimes|boolean',
        ]);

        // Log the device ID
        if ($deviceId) {
            Log::info("SetMixActiveController received deviceId: " . $deviceId);
            // Store it even before queue initialization
            Cache::put("mix:{$mix->id}:device_id", $deviceId, now()->addDay());
        }

        // Update the mix status
        $result = $mix->update(['is_active' => $validated['active']]);

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
                        // Clear any existing pause flags
                        Cache::forget("mix:{$mix->id}:paused");

                        // Set the manual change flag (with a longer duration)
                        Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(10));

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

                            // Set manual change flag
                            Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(10));
                        } else {
                            Cache::put("mix:{$mix->id}:manual_change", true, now()->addSeconds(5));
                            Log::info("Unable to get immediate playback data for mix {$mix->id}, will rely on polling");
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
            // Deactivation - can be handled synchronously as it's faster
            $queueResult = $queueManagementService->stopPlayback($mix->id);

            // Use the method that preserves device ID
            $queueManagementService->clearMixCache($mix->id);

            // IMPORTANT: Broadcast an event to notify other browsers about deactivation
            event(new MixStatusChangedEvent($mix, false));

            return response()->json([
                'activation' => [
                    'success' => true
                ],
                'status' => 'deactivated',
                'message' => 'Playback stopped'
            ]);
        }
    }
}
