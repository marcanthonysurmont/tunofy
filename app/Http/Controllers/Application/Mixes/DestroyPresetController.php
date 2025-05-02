<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Preset;
use Illuminate\Http\RedirectResponse;

class DestroyPresetController extends Controller
{
    public function __invoke(Preset $preset): RedirectResponse
    {
        $this->authorize('delete', $preset);
        
        try {
            $preset->delete();

            return redirect()->back()
                ->with('success', 'Preset deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to delete preset');
        }
    }
}
