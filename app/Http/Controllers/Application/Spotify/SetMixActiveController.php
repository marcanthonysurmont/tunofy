<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetMixActiveRequest;
use App\Models\Mix;
use App\Services\MixActivationService;
use App\Services\QueueManagementService;
use App\Services\SpotifyService;
use Illuminate\Http\JsonResponse;
use App\Events\PlaybackDataUpdatedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SetMixActiveController extends Controller
{
    public function __invoke(
        SetMixActiveRequest $request,
        MixActivationService $mixActivationService,
        QueueManagementService $queueManagementService,
        SpotifyService $spotifyService
    ): JsonResponse {
        $validated = $request->validated();
        $mix = Mix::findOrFail($validated['mix_id']);
        $this->authorize('update', $mix);

        // Activation handling
        $result = $mixActivationService->toggleMixActive($mix, $validated['active']);

        if ($validated['active'] === true) {
            // When activating, return IMMEDIATELY with success
            // But continue processing in the background
            $resetQueue = $request->input('reset_queue', true);

            // Queue the initialization process in the background
            dispatch(function () use ($mix, $resetQueue, $queueManagementService, $spotifyService) {
                // Initialize queue first
                $queueManagementService->initializeQueue($mix, $resetQueue);

                // Start playback after initialization is complete
                $queueManagementService->startPlayback($mix->id, $resetQueue);

                // Get current playback data to broadcast after a short delay
                sleep(0.5);

                $user = $mix->user;
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
            })->afterResponse();

            // Return quickly with success to update UI
            return response()->json([
                'activation' => $result,
                'status' => 'activating',
                'message' => 'Playback starting...'
            ]);
        } else {
            // Deactivation - can be handled synchronously as it's faster
            $queueResult = $queueManagementService->stopPlayback($mix->id);

            return response()->json([
                'activation' => $result,
                'queue' => $queueResult
            ]);
        }
    }
}
