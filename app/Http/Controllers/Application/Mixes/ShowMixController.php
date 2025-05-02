<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Models\Mix;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class ShowMixController extends Controller
{
    public function __invoke(Mix $mix): Response
    {
        $this->authorize('view', $mix);
        $user = Auth::user();

        return Inertia::render('MixSlugPage', [
            'mix' => $mix,
            'songs' => $mix->songs,
            'presets' => Auth::user()->presets,
            'your_mixes' => $user->mixes,
            'joined_mixes' => $user->accessibleMixes
        ]);
    }
}
