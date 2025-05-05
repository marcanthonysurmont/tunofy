<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetMixActiveRequest;
use App\Models\Mix;
use App\Services\MixActivationService;
use App\Services\QueueManagementService;
use Illuminate\Http\JsonResponse;

class SetMixActiveController extends Controller
{
    public function __invoke(SetMixActiveRequest $request, MixActivationService $mixActivationService, QueueManagementService $queueManagementService): JsonResponse
    {
        $validated = $request->validated();

        $mix = Mix::findOrFail($validated['mix_id']);

        $this->authorize('update', $mix);

        // Activation handling
        $result = $mixActivationService->toggleMixActive($mix, $validated['active']);

        // Queue handling
        $queueResult = null;

        if ($validated['active'] === true) {
            // When activating, ensure we properly initialize the queue first
            $resetQueue = $request->input('reset_queue', true); // Default to true to fix first song skip

            // Initialize queue first
            $queueManagementService->initializeQueue($mix, $resetQueue);

            // Short pause to ensure queue is properly initialized
            usleep(100000); // 100ms pause

            // Start playback after initialization is complete
            $queueResult = $queueManagementService->startPlayback($mix->id, $resetQueue);
        } else {
            // Stop and clear queue when deactivating
            $queueResult = $queueManagementService->stopPlayback($mix->id);
        }

        return response()->json([
            'activation' => $result,
            'queue' => $queueResult
        ]);
    }
}
