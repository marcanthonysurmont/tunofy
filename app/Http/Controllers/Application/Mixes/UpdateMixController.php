<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMixRequest;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;

class UpdateMixController extends Controller
{
    public function __invoke(UpdateMixRequest $request, Mix $mix): RedirectResponse
    {
        $this->authorize('update', $mix);

        $validated = $request->validated();

        try {
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('mix_avatars', 'public');
            }

            $mix->update([
                'name' => $validated['name'],
                // 'is_public' => $validated['is_public'],
                // 'preset_id' => $validated['preset_id'],
                'avatar' => $avatarPath ?? $mix->avatar,
            ]);

            return redirect()
                ->route('mix.show', $mix)
                ->with('success', 'Mix updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to update mix. Please try again later.');
        }
    }
}
