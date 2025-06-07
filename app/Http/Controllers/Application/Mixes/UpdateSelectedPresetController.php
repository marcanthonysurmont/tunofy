<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSelectedPresetRequest;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;
use Exception;

class UpdateSelectedPresetController extends Controller
{
    public function __invoke(UpdateSelectedPresetRequest $request, Mix $mix): RedirectResponse
    {
        $validated = $request->validated();

        try {
            if($mix->is_active) {
                return redirect()->back()
                    ->with('error', 'You cannot change the preset while the mix is active');
            }

            $mix->update([
                'preset_id' => $validated['preset_id'],
            ]);

            return redirect()->back();
        } catch (Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to update selected preset');
        }
    }
}
