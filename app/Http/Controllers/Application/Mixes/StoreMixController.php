<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Models\Mix;
use App\Models\Preset;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreMixRequest;
use App\Models\GlobalUserStat;
use Illuminate\Support\Facades\DB;
use Exception;

class StoreMixController extends Controller
{
    public function __invoke(StoreMixRequest $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->type !== 'premium') {
            return redirect()->back()
                ->with('danger', 'You must be a premium user to create a mix.');
        }

        $validated = $request->validated();

        try {
            $avatarPath = null;
            if ($request->hasFile('image')) {
                $avatarPath = $request->file('image')->store('mix_avatars', 'public');
            }

            $mix = Mix::create([
                'user_id' => $user->id,
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

            GlobalUserStat::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'mixes_created' => DB::raw('mixes_created + 1'),
                ],
            );

            return redirect()->back()
                ->with('success', 'Mix created successfully!');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to create mix');
        }
    }
}
