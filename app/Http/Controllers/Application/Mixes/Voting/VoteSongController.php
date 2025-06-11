<?php

namespace App\Http\Controllers\Application\Mixes\Voting;

use App\Events\StatUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\VoteSongRequest;
use App\Models\QueueSong;
use App\Events\VoteUpdatedEvent;
use App\Services\Songs\VotingService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VoteSongController extends Controller
{
    public function __invoke(QueueSong $queueSong, VoteSongRequest $request, VotingService $votingService): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $user = Auth::user();
            $voteType = $validated['vote_type'];

            // Direct processing without transaction
            $votingService->processVote($queueSong, $user->id, $voteType);
            $votingService->updateQueueOrder($queueSong);

            // Dispatch events immediately (as was done before)
            $mix = $queueSong->mix;
            VoteUpdatedEvent::dispatch($mix);
            StatUpdatedEvent::dispatch($mix);

            return redirect()->back();
        } catch (Exception $e) {
            Log::error("Error processing vote: " . $e->getMessage());
            return redirect()->back();
        }
    }
}
