<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePresetRequest;
use App\Models\Preset;
use Illuminate\Http\RedirectResponse;
use Exception;

class UpdatePresetController extends Controller
{
    public function __invoke(UpdatePresetRequest $request, Preset $preset): RedirectResponse
    {
        $this->authorize('update', $preset->mix);

        $validated = $request->validated();

        try {
            $preset->update([
                'batch_size' => $validated['batch_size'],
                'requires_approval' => $validated['requires_approval'],
                'voting_enabled' => $validated['voting_enabled'],
                'kill_percentage' => $validated['kill_percentage'],
                'priority_boost_new' => $validated['priority_boost_new'],
                'auto_remove_negative' => $validated['auto_remove_negative'],
                'emoji_chat_enabled' => $validated['emoji_chat_enabled'],
            ]);

            return redirect()->back()
                ->with('success', 'Preset updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to update preset');
        }
    }
}
