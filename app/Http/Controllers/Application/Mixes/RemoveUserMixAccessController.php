<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\RemoveUserMixAccessRequest;
use App\Models\Mix;
use App\Models\MixAccess;
use Illuminate\Support\Facades\Auth;

class RemoveUserMixAccessController extends Controller
{
    public function __invoke(RemoveUserMixAccessRequest $request, Mix $mix)
    {
        $validated = $request->validated();

        $user = Auth::user();

        $this->authorize('removeUserMixAccess', [$mix, $validated['user_id']]);

        MixAccess::where('mix_id', $mix->id)
            ->where('user_id', $validated['user_id'])
            ->delete();

        if ($user->id === $validated['user_id']) {
            return redirect()->route('app')
                ->with('success', 'You have left the mix successfully.');
        }
        return redirect()->back()
            ->with('success', 'User access removed successfully.');
    }
}
