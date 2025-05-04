<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Requests\SetMixActiveRequest;
use App\Services\MixActivationService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Mix;

class SetMixActiveController extends Controller
{
    public function __invoke(SetMixActiveRequest $request, MixActivationService $mixActivationService): JsonResponse
    {
        $validated = $request->validated();

        $mix = Mix::findOrFail($validated['mix_id']);

        $this->authorize('update', $mix);

        $result = $mixActivationService->toggleMixActive($mix, $validated['active']);

        return response()->json($result);
    }
}
