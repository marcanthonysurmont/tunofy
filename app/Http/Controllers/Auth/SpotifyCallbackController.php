<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class SpotifyCallbackController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $spotifyUser = Socialite::driver('spotify')->user();

        $user = User::updateOrCreate(
            ['spotify_id' => $spotifyUser->getId()],
            [
                'name' => $spotifyUser->getName() ?? 'User',
                'email' => $spotifyUser->getEmail(),
                'avatar' => $spotifyUser->getAvatar(),
                'access_token' => $spotifyUser->token,
                'refresh_token' => $spotifyUser->refreshToken,
                'token_expires_in' => $spotifyUser->expiresIn,
            ]
        );

        Auth::login($user);

        return redirect('/app')->with('success', 'Logged in successfully!');
    }
}
