<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Resources\UserResource;
use App\Models\Mix;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MixResource;

class ShowMixController extends Controller
{
    public function __invoke(Mix $mix): Response
    {
        $this->authorize('view', $mix);
        
        $user = Auth::user();

        $mix->load(['songs.user', 'presets', 'user', 'collaborators']);
        $user->load(['mixes', 'accessibleMixes']);

        return Inertia::render('MixSlugPage', [
            'mix' => MixResource::make($mix)->jsonSerialize(),
            'collaborators' => UserResource::collection($mix->collaborators)->jsonSerialize(),
            'presets' => $mix->all_presets,
            'your_mixes' => $user->mixes,
            'joined_mixes' => $user->accessibleMixes,
            'owner' => $mix->user
        ]);
    }
}
