<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Resources\CollaboratorResource;
use App\Http\Resources\SongResource;
use App\Models\Mix;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MixResource;
use App\Services\Spotify\SpotifyService;

class ShowMixController extends Controller
{
    public function __invoke(Mix $mix, SpotifyService $spotifyService, string $tab = 'overview'): Response
    {
        $this->authorize('view', $mix);

        $user = Auth::user();

        $mix->load(['presets', 'user', 'collaborators', 'themes']);
        $user->load(['mixes', 'accessibleMixes']);

        $songs = $mix->songs()->with('user')->paginate(20);

        // Get the controlling user ID first
        $controllingUserId = $mix->co_dj_id ?: $mix->user_id;

        // Then use it in the scope call
        $activeConflictingMixes = Mix::conflictingActiveMixes($mix->id, $controllingUserId)->get();

        // Get Spotify devices for the mix owner
        $devices = $spotifyService->getUserDevices($user);
        $collaborators = $mix->collaborators()->orderBy('created_at', 'desc')->paginate(2);
        $collaborators->withPath("/{$mix->slug}/manage");

        return Inertia::render('MixSlugPage', [
            'mix' => fn () => MixResource::make($mix)->jsonSerialize(),
            'songs' => fn () => SongResource::collection($songs)->jsonSerialize(),
            'collaborators' => fn () => CollaboratorResource::collection($collaborators),
            'activeConflictingMixes' => fn () => $activeConflictingMixes,
            'themes' => fn () => $mix->getThemeSettings(),
            'presets' => fn () => $mix->all_presets,
            'your_mixes' => fn () => $user->mixes,
            'joined_mixes' => fn () => $user->accessibleMixes,
            'owner' => fn () => $mix->user,
            'devices' => fn () => $devices,
            'activeTab' => fn () => $tab,
        ]);
    }
}
