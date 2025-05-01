<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\General\ShowLandingPageController;
use App\Http\Controllers\General\ShowDPAPageController;
use App\Http\Controllers\General\ShowPrivacyPageController;
use App\Http\Controllers\General\ShowTermsOfUsePageController;

use App\Http\Controllers\Auth\RedirectToSpotifyController;
use App\Http\Controllers\Auth\SpotifyCallbackController;

use App\Http\Controllers\Application\ShowAppPageController;

use App\Http\Controllers\Application\Mixes\StoreMixController;
use App\Http\Controllers\Application\Mixes\ShowMixController;
use App\Http\Controllers\Application\Mixes\UpdateMixController;
use App\Http\Controllers\Application\Mixes\DestroyMixController;
use App\Http\Controllers\Application\Mixes\AddSongToMixController;
use App\Http\Controllers\Application\Mixes\RemoveSongFromMixController;

use App\Http\Controllers\Application\Spotify\SearchSongController;

Route::get('/', ShowLandingPageController::class);
Route::get('/dpa', ShowDPAPageController::class);
Route::get('/privacy', ShowPrivacyPageController::class);
Route::get('/terms-of-use', ShowTermsOfUsePageController::class);

Route::middleware('auth')->group(function () {
    Route::prefix('/app')->group(function () {
        Route::get('/', ShowAppPageController::class)->name('app');
        Route::get('/{mix:slug}', ShowMixController::class)->name('mix.show');

        Route::prefix('/mix')->name('mix.')->group(function () {
            Route::post('/store', StoreMixController::class)->name('store');
            Route::put('/update/{mix}', UpdateMixController::class)->name('update');
            Route::delete('/destroy/{mix}', DestroyMixController::class)->name('destroy');
            Route::post('/add-song/{mix}', AddSongToMixController::class)->name('add-song');
            Route::delete('/remove-song/{song}', RemoveSongFromMixController::class)->name('remove-song');
        });
    });

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
