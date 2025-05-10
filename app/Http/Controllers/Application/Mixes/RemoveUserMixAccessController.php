<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\RemoveUserMixAccessRequest;
use App\Models\Mix;
use App\Models\MixAccess;

class RemoveUserMixAccessController extends Controller
{
    public function __invoke(RemoveUserMixAccessRequest $request, Mix $mix)
    {
        $validated = $request->validated();
        
        MixAccess::where('mix_id', $mix->id)
            ->where('user_id', $validated['user_id'])
            ->delete();

        return redirect()->back()
            ->with('success', 'User access removed successfully.');
    }
}
