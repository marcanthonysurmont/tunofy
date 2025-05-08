<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Services\CoDJManagementService;

class RemoveCoDJController extends Controller
{
    public function __invoke(Mix $mix, CoDJManagementService $coDJService): RedirectResponse
    {
        $this->authorize('assignCoDJ', $mix);
        
        try {
            $coDJService->removeCoDJ($mix);
            
            return redirect()->back()
                ->with('success', 'Co-DJ removed successfully.');
        } catch (\Exception $e) {
            Log::error("Error removing co-DJ: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error removing co-DJ');
        }
    }
}
