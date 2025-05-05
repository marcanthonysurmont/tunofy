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

        // Activation handling (now separate from queue management)
        $result = $mixActivationService->toggleMixActive($mix, $validated['active']);

        // Queue handling (done separately)
        $queueResult = null;

        if ($validated['active'] === true) {
            // Initialize and start queue when activating
            $queueManagementService->initializeQueue($mix);
            $queueResult = $queueManagementService->startPlayback($mix->id);
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
