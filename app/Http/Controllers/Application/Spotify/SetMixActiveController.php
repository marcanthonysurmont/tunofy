<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetMixActiveRequest;
use App\Models\Mix;
use App\Services\Queue\MixActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class SetMixActiveController extends Controller
{
    public function __invoke(
        SetMixActiveRequest $request,
        Mix $mix,
        MixActivationService $activationService
    ): JsonResponse {
        $this->authorize('controlPlayback', $mix);
        
        try {
            // Get inputs
            $validated = $request->validated();
            $deviceId = $validated['deviceId'] ?? null;

            // Determine action: activate or deactivate
            if ($validated['active'] === true) {
                // Check for conflicting mixes
                $conflictingMix = $activationService->checkConflictingMixes($mix);
                if ($conflictingMix) {
                    return response()->json([
                        'success' => false,
                        'message' => "You already have an active mix with '{$conflictingMix->name}'.",
                    ], 400);
                }

                // Activate the mix
                $result = $activationService->activateMix($mix, $deviceId);

                return response()->json($result);
            } else {
                // DEACTIVATION FLOW
                $result = $activationService->deactivateMix($mix);
                
                return response()->json($result);
            }
        } catch (Exception $e) {
            // Log error and return response
            Log::error("Error in SetMixActiveController: " . $e->getMessage());

            return response()->json([
                'success' => false,
            ], 500);
        }
    }
}
