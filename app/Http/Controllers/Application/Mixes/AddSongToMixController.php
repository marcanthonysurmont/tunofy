<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddSongToMixRequest;
use App\Models\Mix;
use App\Models\PlaybackSession;
use App\Services\Queue\QueueBuilderService;
use App\Services\Queue\QueueManagementService;
use App\Services\Songs\SongService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AddSongToMixController extends Controller
{
    public function __invoke(
        AddSongToMixRequest $request,
        Mix $mix,
        SongService $songService,
        QueueBuilderService $queueBuilderService,
        QueueManagementService $queueManagementService
    ): RedirectResponse {
        $this->authorize('addSongs', $mix);

        try {
            // Create the song and update stats
            $song = $songService->createSongWithStats($mix, $request->validated(), Auth::user());

            // Handle active mix queue management
            $session = PlaybackSession::where('mix_id', $mix->id)
                ->where('is_active', true)
                ->latest('started_at')
                ->first();

            if ($session && $mix->is_active) {
                // Update the shuffled IDs cache
                $queueBuilderService->updateShuffledIdsCache($mix, $song);

                // Try to add directly to queue if below threshold
                $queueManagementService->addSongToQueueIfBelowThreshold($mix, $song, $session);

                // Extend queue if needed
                $queueManagementService->extendQueueIfNeeded($mix);
            }

            return redirect()->back()->with('success', 'Song added to mix successfully.');
        } catch (Exception $e) {
            if ($e->getCode() == 23000) {
                return redirect()->back()->with('error', 'Song already exists in the mix.');
            }

            Log::error("Error adding song to mix: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to add song to mix');
        }
    }
}
