<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignCoDJRequest;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Services\Collaboration\CoDJManagementService;
use App\Events\MixStatusChangedEvent;

class AssignCoDJController extends Controller
{
    public function __invoke(AssignCoDJRequest $request, Mix $mix, CoDJManagementService $coDJService): RedirectResponse
    {
        $this->authorize('assignCoDJ', $mix);

        $validated = $request->validated();
        
        try {
            $user = User::findOrFail($validated['user_id']);
            $coDJService->assignCoDJ($mix, $user);
            
            $otherMixes = Mix::otherMixesForUser($mix->id)->get();
            $changeReason = 'other_mix';

            foreach ($otherMixes as $otherMix) {
                event(new MixStatusChangedEvent($otherMix, $otherMix->is_active, $changeReason));
            }
            return redirect()->back()
                ->with('success', 'Co-DJ assigned successfully.');
        } catch (\Exception $e) {
            Log::error("Error assigning co-DJ: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error assigning co-DJ.');
        }

    }
}