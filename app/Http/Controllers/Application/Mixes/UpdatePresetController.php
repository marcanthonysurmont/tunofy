<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePresetRequest;
use App\Models\Preset;
use Illuminate\Http\RedirectResponse;

class UpdatePresetController extends Controller
{
    public function __invoke(UpdatePresetRequest $request, Preset $preset): RedirectResponse
    {
        $this->authorize('update', $preset);
        
        $validated = $request->validated();

        try {
            $preset->update([
                'name' => $validated['name'],
                'batch_size' => $validated['batch_size'],
                'max_songs' => $validated['max_songs'],
                'num_rounds' => $validated['num_rounds'],
                'requires_approval' => $validated['requires_approval'],
                'voting_enabled' => $validated['voting_enabled'],
                'kill_percentage_percent' => $validated['kill_percentage_percent'],
                'priority_boost_new' => $validated['priority_boost_new'],
                'auto_remove_negative' => $validated['auto_remove_negative'],
                'emoji_chat_enabled' => $validated['emoji_chat_enabled'],
            ]);

            return redirect()->back()
                ->with('success', 'Preset updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to update preset');
        }
    }
}
