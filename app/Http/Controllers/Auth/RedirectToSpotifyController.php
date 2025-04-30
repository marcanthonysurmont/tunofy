<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class RedirectToSpotifyController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return Socialite::driver('spotify')->scopes(['user-read-email'])->redirect();
    }
}
