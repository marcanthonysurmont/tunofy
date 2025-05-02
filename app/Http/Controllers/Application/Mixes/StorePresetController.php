<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Requests\StorePresetRequest;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\Preset;
use Illuminate\Support\Facades\Auth;

class StorePresetController extends Controller
{
    public function __invoke(StorePresetRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        try {
            Preset::create([
                'name' => $validated['name'],
                'user_id' => Auth::id(),
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
                ->with('success', 'Preset created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to create preset');
        }

    }
}
