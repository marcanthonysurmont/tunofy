<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Models\Mix;
use App\Models\Preset;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreMixRequest;

class StoreMixController extends Controller
{
    public function __invoke(StoreMixRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $avatarPath = null;
            if ($request->hasFile('image')) {
                $avatarPath = $request->file('image')->store('mix_avatars', 'public');
            }

            $mix = Mix::create([
                'user_id' => Auth::id(),
                'name' => $validated['name'],
                'is_public' => $validated['is_public'],
                'avatar' => $avatarPath,
            ]);

            Preset::create([
                'name' => 'Custom',
                'description' => 'Tailor the settings of your mix according to your preferences and needs.',
                'mix_id' => $mix->id,
                'is_system' => false,
                'batch_size' => 1,
                'max_songs' => 1,
                'num_rounds' => 1,
                'requires_approval' => 0,
                'voting_enabled' => 0,
                'kill_percentage_percent' => 0,
                'priority_boost_new' => 0,
                'auto_remove_negative' => 0,
                'emoji_chat_enabled' => 0,
            ]);
            return redirect()->route('mix.show', $mix->slug)
                ->with('success', 'Mix created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to create mix');
        }
    }
}
