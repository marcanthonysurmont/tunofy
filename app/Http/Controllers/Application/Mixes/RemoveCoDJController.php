<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Services\CoDJManagementService;
use App\Events\MixStatusChangedEvent;

class RemoveCoDJController extends Controller
{
    public function __invoke(Mix $mix, CoDJManagementService $coDJService): RedirectResponse
    {
        $this->authorize('assignCoDJ', $mix);
        
        try {
            $coDJService->removeCoDJ($mix);
            
            $otherMixes = Mix::otherMixesForUser($mix->id)->get();
            $changeReason = 'other_mix';

            foreach ($otherMixes as $otherMix) {
                event(new MixStatusChangedEvent($otherMix, $otherMix->is_active, $changeReason));
            }

            return redirect()->back()
                ->with('success', 'Co-DJ removed successfully.');
        } catch (\Exception $e) {
            Log::error("Error removing co-DJ: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error removing co-DJ');
        }
    }
}
