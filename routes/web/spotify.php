<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Application\Spotify\SearchSongController;
use App\Http\Controllers\Application\Spotify\GetTrackPreviewController;
use App\Http\Controllers\Application\Spotify\GetSpotifyPlaylistsController;
use App\Http\Controllers\Application\Spotify\RequestSpotifyPlayerStatusController;
use App\Http\Controllers\Application\Spotify\SetMixActiveController;
use App\Http\Controllers\Application\Spotify\PauseMixPlaybackController;
use App\Http\Controllers\Application\Spotify\ResumeMixPlaybackController;
use App\Http\Controllers\Application\Spotify\PlayNextSongController;
use App\Http\Controllers\Application\Spotify\PlayPreviousSongController;
use App\Http\Controllers\Application\Spotify\GetDevicesController;
use App\Http\Controllers\Application\Spotify\TransferPlaybackController;

// Spotify API routes
Route::prefix('api/spotify')->name('api.spotify.')->group(function () {
    // Search and metadata
    Route::post('/search', SearchSongController::class)->name('search');
    Route::post('/track-preview', GetTrackPreviewController::class)->name('track-preview');
    Route::post('/get-playlists', GetSpotifyPlaylistsController::class)->name('get-playlist');
    Route::get('/devices', GetDevicesController::class)->name('devices');

    // Playback control
    Route::get('/request-status/{mix}', RequestSpotifyPlayerStatusController::class)->name('request-status');
    Route::post('/set-mix-active/{mix}', SetMixActiveController::class)->name('set-mix-active');
    Route::post('/pause-mix/{mix}', PauseMixPlaybackController::class)->name('pause-mix');
    Route::post('/resume-mix/{mix}', ResumeMixPlaybackController::class)->name('resume-mix');
    Route::post('/skip-song/{mix}', PlayNextSongController::class)->name('skip-song');
    Route::post('/previous-song/{mix}', PlayPreviousSongController::class)->name('previous-song');
    Route::post('/transfer-playback/{mix}', TransferPlaybackController::class)->name('transfer-playback');
});
