<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\General\ShowLandingPageController;
use App\Http\Controllers\General\ShowDPAPageController;
use App\Http\Controllers\General\ShowPrivacyPageController;
use App\Http\Controllers\General\ShowTermsOfUsePageController;

use App\Http\Controllers\Auth\RedirectToSpotifyController;
use App\Http\Controllers\Auth\SpotifyCallbackController;

use App\Http\Controllers\Application\ShowAppPageController;
use App\Http\Controllers\Application\Spotify\SearchSongController;

Route::get('/', ShowLandingPageController::class);
Route::get('/dpa', ShowDPAPageController::class);
Route::get('/privacy', ShowPrivacyPageController::class);
Route::get('/terms-of-use', ShowTermsOfUsePageController::class);

Route::middleware('auth')->group(function () {
    Route::get('/app', ShowAppPageController::class)->name('app');

    Route::prefix('api/spotify')->name('api.spotify.')->group(function () {
        Route::post('/search', SearchSongController::class)->name('search');
    });
});

Route::middleware('guest')->group(function () {
    Route::prefix('/auth')->group(function () {
        Route::get('/login/spotify', RedirectToSpotifyController::class)->name('login');
        Route::get('/spotify/callback', SpotifyCallbackController::class)->name('callback');
    });
});
