<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class ToggleMixIsPublicController extends Controller
{
    public function __invoke(Mix $mix): RedirectResponse
    {
        $this->authorize('update', $mix);
        
        try {
            // Toggle the is_public status
            $mix->update(['is_public' => !$mix->is_public]);

            $message = $mix->is_public ? 'Mix is now public.' : 'Mix is now private.';

            return redirect()->back()
                ->with('success', $message);

        } catch (Exception $e) {
            Log::error("Failed to toggle mix visibility: " . $e->getMessage());
            return redirect()->back()
                ->with('danger', 'Failed to apply changes. Please try again later.');
        }
    }
}
