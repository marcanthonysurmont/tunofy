<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMixRequest;
use App\Models\Mix;

class UpdateMixController extends Controller
{
    public function __invoke(UpdateMixRequest $request, Mix $mix)
    {
        $this->authorize('update', $mix);
        
        $validated = $request->validated();

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('', 'mix_avatars');
        }

        $mix->update([
            'name' => $validated['name'],
            'is_public' => $validated['is_public'],
            'preset_id' => $validated['preset_id'],
            'avatar' => $avatarPath ?? $mix->avatar,
        ]);

        return redirect()->back()
            ->with('success', 'Mix updated successfully.');
    }
}
