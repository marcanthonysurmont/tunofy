<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class RedirectToSpotifyController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return Socialite::driver('spotify')
            ->scopes([
                'user-read-email',
                'user-read-recently-played',
                'user-read-private',
                'user-read-playback-state',
                'user-modify-playback-state',
                'user-read-currently-playing',
            ])
            ->with(['show_dialog' => 'true'])
            ->redirect();
    }
}
