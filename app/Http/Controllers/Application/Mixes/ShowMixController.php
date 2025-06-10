<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Resources\CollaboratorResource;
use App\Http\Resources\SongResource;
use App\Models\Mix;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MixResource;
use App\Services\Spotify\SpotifyService;
use App\Services\Stats\MixStatService;

class ShowMixController extends Controller
{
    public function __invoke(
        Mix $mix,
        SpotifyService $spotifyService,
        MixStatService $mixStatService,
        string $tab = 'overview'
    ) {
        $this->authorize('view', $mix);

        // Core song data
        $songsQuery = $mix->songs()->with('user');
        $songs = (clone $songsQuery)->paginate(15);
        $mixDuration = $mixStatService->calculateMixDuration($songsQuery);

        if (request()->wantsJson()) {
            return SongResource::collection($songs);
        }

        $user = Auth::user();

        // Load relationships
        $mix->load(['presets', 'user', 'collaborators', 'themes']);
        $user->load(['mixes', 'accessibleMixes']);

        // Get conflicting mixes
        $controllingUserId = $mix->co_dj_id ?: $mix->user_id;
        $activeConflictingMixes = Mix::conflictingActiveMixes($mix->id, $controllingUserId)->get();

        // Get pending songs
        $allPendingSongs = $mixStatService->getSortedPendingSongs($mix);

        // Get votable songs
        $votableSongs = $mix->filterVotableSongs($allPendingSongs);

        // Get Spotify devices and collaborators
        $devices = $spotifyService->getUserDevices($user);

        // Conditionally load collaborators and presets
        $collaborators = collect();
        $presets = collect();

        if($user->id === $mix->user_id) {
            $collaborators = $mix->collaborators()->orderBy('created_at', 'desc')->paginate(20);
            $collaborators->withPath("/{$mix->slug}/manage");

            $presets = $mix->all_presets;
        }

        return Inertia::render('MixSlugPage', [
            'mix' => fn () => MixResource::make($mix)->jsonSerialize(),
            'songs' => fn () => SongResource::collection($songs),
            'mixDuration' => fn () => $mixDuration,
            'collaborators' => fn () => CollaboratorResource::collection($collaborators),
            'activeConflictingMixes' => fn () => $activeConflictingMixes,
            'allPendingSongs' => fn () => $allPendingSongs,
            'votableSongs' => fn () => $votableSongs,
            'themes' => fn () => $mix->getThemeSettings(),
            'presets' => fn () => $presets,
            'your_mixes' => fn () => $user->mixes,
            'joined_mixes' => fn () => $user->accessibleMixes,
            'owner' => fn () => $mix->user,
            'devices' => fn () => $devices,
            'activeTab' => fn () => $tab,
            'mixStats' => fn () => $mix->mixStats,
            'userStats' => fn () => $mix->mixUserStats
        ]);
    }
}
