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

Route::middleware('auth')->group(function () {
    Route::get('/app', function () {
        return Inertia::render('TestPage');
    });
});

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return Inertia::render('LoginPage');
    })->name('login');
    
    Route::prefix('/auth')->name('auth.')->group(function () {
        Route::get('/login/spotify', RedirectToSpotifyController::class)->name('login');
        Route::get('/spotify/callback', SpotifyCallbackController::class)->name('callback');
    });
});

