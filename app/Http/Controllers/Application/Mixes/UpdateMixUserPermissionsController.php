<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMixUserPermissionsRequest;
use App\Models\Mix;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UpdateMixUserPermissionsController extends Controller
{
    public function __invoke(Mix $mix, UpdateMixUserPermissionsRequest $request): RedirectResponse
    {
        $this->authorize('update', $mix);

        $validated = $request->validated();

        $user = User::findOrFail($validated['user_id']);

        $mixAccess = $user->mixAccesses()
            ->where('mix_id', $mix->id)
            ->first();

        if($mixAccess && $mixAccess->permission === $validated['role']) {
            return redirect()->back()
                ->with('error', 'User already has this permission.');
        }

        if ($mixAccess) {
            $mixAccess->update(['permission' => $validated['role']]);

            return redirect()->back()
                ->with('success', 'User permissions updated successfully.');
        } 

        return redirect()->back()
            ->with('error', 'No permissions found for this user.');
    }
}
