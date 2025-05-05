<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSelectedPresetRequest;
use App\Models\Mix;
use App\Models\Preset;
use Illuminate\Http\RedirectResponse;

class UpdateSelectedPresetController extends Controller
{
    public function __invoke(UpdateSelectedPresetRequest $request, Mix $mix): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $mix->update([
                'preset_id' => $validated['preset_id'],
            ]);
            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to update selected preset');
        }
    }
}
