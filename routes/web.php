<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\ShowLandingPageController;
use App\Http\Controllers\General\ShowDPAPageController;
use App\Http\Controllers\General\ShowPrivacyPageController;
use App\Http\Controllers\General\ShowTermsOfUsePageController;

// App subdomain routes
Route::domain('app.' . parse_url(config('app.url'), PHP_URL_HOST))->group(function () {
    // Guest routes (auth)
    require __DIR__.'/web/auth.php';

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        require __DIR__.'/web/app.php';
        require __DIR__.'/web/mixes.php';
        require __DIR__.'/web/spotify.php';
    });
});

// Public routes
Route::get('/', ShowLandingPageController::class)->name('landing');
Route::get('/dpa', ShowDPAPageController::class);
Route::get('/privacy', ShowPrivacyPageController::class);
Route::get('/terms-of-use', ShowTermsOfUsePageController::class);
