<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\ShowLoginPageController;
use App\Http\Controllers\Auth\RedirectToSpotifyController;
use App\Http\Controllers\Auth\SpotifyCallbackController;

use App\Http\Controllers\Application\ShowAppPageController;

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
    Route::get('/app', ShowAppPageController::class);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', ShowLoginPageController::class)->name('login');
    
    Route::prefix('/auth')->name('auth.')->group(function () {
        Route::get('/login/spotify', RedirectToSpotifyController::class)->name('login');
        Route::get('/spotify/callback', SpotifyCallbackController::class)->name('callback');
    });
});

