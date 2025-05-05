<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Requests\StoreMixRequest;
use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Preset;

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
            
            return redirect()->back()
                ->with('success', 'Mix created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to create mix');
        }
    }
}
