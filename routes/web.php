<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\RedirectToSpotifyController;
use App\Http\Controllers\Auth\SpotifyCallbackController;

Route::get('/', function () {
    return view('landing');
});

Route::get('/dpa', function () {
    return view('gdpr');
});

Route::get('/privacy', function () {
    return view('privacy');
});

Route::get('/terms', function () {
    return view('termsofuse');
});

//

Route::get('/login', function () {
    return Inertia::render('LoginPage');
});


//

Route::get('/app', function () {
    return Inertia::render('TestPage');
});

Route::get('auth/login/spotify', RedirectToSpotifyController::class);
Route::get('auth/spotify/callback', SpotifyCallbackController::class);