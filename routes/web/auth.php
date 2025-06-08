<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\DeleteAccountController;
use App\Http\Controllers\Auth\RedirectToSpotifyController;
use App\Http\Controllers\Auth\SpotifyCallbackController;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::prefix('/auth')->group(function () {
        Route::get('/login/spotify', RedirectToSpotifyController::class)->name('login');
        Route::get('/spotify/callback', SpotifyCallbackController::class)->name('callback');
    });
});

// These are auth-related but require the user to be authenticated
Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::post('/delete-account', DeleteAccountController::class)->name('delete-account');
});
