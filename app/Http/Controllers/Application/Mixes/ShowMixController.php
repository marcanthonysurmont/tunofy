<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Resources\CollaboratorResource;
use App\Http\Resources\UserResource;
use App\Models\Mix;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MixResource;
use App\Services\SpotifyService;

class ShowMixController extends Controller
{
    public function __invoke(Mix $mix, SpotifyService $spotifyService, string $tab = 'overview'): Response
    {
        $this->authorize('view', $mix);

        $user = Auth::user();

        $mix->load(['songs.user', 'presets', 'user', 'collaborators']);
        $user->load(['mixes', 'accessibleMixes']);

        // Get Spotify devices for the mix owner
        $devices = $spotifyService->getUserDevices($user);
        $collaborators = $mix->collaborators()->orderBy('created_at', 'asc')->paginate(15);
   
        return Inertia::render('MixSlugPage', [
            'mix' => fn() => MixResource::make($mix)->jsonSerialize(),
            'collaborators' => fn() => CollaboratorResource::collection($collaborators)->jsonSerialize(),
            'collaborators_premium' => fn() => UserResource::collection($collaborators->where('type', 'premium'))->jsonSerialize(),
            'presets' => fn() => $mix->all_presets,
            'your_mixes' => fn() => $user->mixes,
            'joined_mixes' => fn() => $user->accessibleMixes,
            'owner' => fn() => $mix->user,
            'devices' => fn() => $devices,
            'activeTab' => fn() => $tab,
        ]);
    }
}
