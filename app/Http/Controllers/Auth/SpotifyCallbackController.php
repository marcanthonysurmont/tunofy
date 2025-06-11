<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Exception;

class SpotifyCallbackController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        try {
            $spotifyUser = Socialite::driver('spotify')->user();

            $expiresAt = null;

            if ($spotifyUser->expiresIn) {
                $expiresAt = now()->addSeconds($spotifyUser->expiresIn);
            }

            $user = User::updateOrCreate(
                ['spotify_id' => $spotifyUser->getId()],
                [
                    'name' => $spotifyUser->getName() ?? 'User',
                    'email' => $spotifyUser->getEmail(),
                    'avatar' => $spotifyUser->getAvatar(),
                    'type' => $spotifyUser->user['product'],
                    'access_token' => $spotifyUser->token,
                    'refresh_token' => $spotifyUser->refreshToken,
                    'token_expires_at' => $expiresAt,
                ]
            );

            Auth::login($user);

            $appUrl = 'https://app.' . parse_url(config('app.url'), PHP_URL_HOST);
            return redirect()->away($appUrl)->with('success', 'Logged in successfully!');
        } catch (Exception $e) {
            $mainUrl = config('app.url');
            return redirect()->away($mainUrl);
        }
    }
}
