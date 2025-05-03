<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Requests\SetMixActiveRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Support\Facades\Cache;
use App\Events\MixStatusChanged;
use Illuminate\Support\Facades\Log;

class SetMixActiveController extends Controller
{
    public function __invoke(SetMixActiveRequest $request): JsonResponse
    {
        $mix = Mix::findOrFail($request->input('mix_id'));

        // Authorization check...
        $this->authorize('update', $mix);

        $isActive = $request->boolean('active');
        $wasActive = $mix->is_active;

        \Log::info("Setting mix {$mix->id} active status: {$wasActive} → {$isActive}");

        // Update the mix active status
        $mix->is_active = $isActive;
        $mix->save();

        // IMMEDIATELY broadcast the status change (no queue)
        \Log::info("Broadcasting MixStatusChanged event for mix {$mix->id}, active={$isActive}");

        // Use broadcast now to skip the queue
        broadcast(new MixStatusChanged($mix, $isActive))->toOthers();

        // If deactivating, clear the cache
        if (!$isActive && $wasActive) {
            \Log::info("Clearing cache for deactivated mix {$mix->id}");

            // Clear main playback cache
            Cache::forget("spotify:playback:{$mix->id}");
        }

        // Start the dedicated polling job if activating (this can still use the queue)
        if ($isActive && !$wasActive) {
            \Log::info("Dispatching polling job for mix {$mix->id}");
            \App\Jobs\PollSpotifyMix::dispatch($mix);

            // Return the cached playback data with the response (if available)
            $cacheKey = "spotify:playback:{$mix->id}";
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                \Log::info("Sending cached playback data to activating client");
                return response()->json([
                    'success' => true,
                    'is_active' => (bool) $mix->is_active,
                    'playback_data' => $cachedData
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'is_active' => (bool) $mix->is_active
        ]);
    }
}
