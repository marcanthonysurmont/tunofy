<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Application\Mixes\ShowMixController;
use App\Http\Controllers\Application\Mixes\StoreMixController;
use App\Http\Controllers\Application\Mixes\UpdateMixController;
use App\Http\Controllers\Application\Mixes\DestroyMixController;
use App\Http\Controllers\Application\Mixes\AddSongToMixController;
use App\Http\Controllers\Application\Mixes\RemoveSongFromMixController;
use App\Http\Controllers\Application\Mixes\GenerateMixCodeController;
use App\Http\Controllers\Application\Mixes\JoinMixController;
use App\Http\Controllers\Application\Mixes\RemoveSessionCodeController;
use App\Http\Controllers\Application\Mixes\AssignCoDJController;
use App\Http\Controllers\Application\Mixes\RemoveCoDJController;
use App\Http\Controllers\Application\Mixes\ToggleMixIsPublicController;
use App\Http\Controllers\Application\Mixes\SearchUserController;
use App\Http\Controllers\Application\Mixes\RemoveUserMixAccessController;
use App\Http\Controllers\Application\Mixes\ImportSpotifyPlaylistController;
use App\Http\Controllers\Application\Mixes\UpdateMixThemeController;
use App\Http\Controllers\Application\Mixes\UpdateMixUserPermissionsController;
use App\Http\Controllers\Application\Mixes\GetMixSongsController;
use App\Http\Controllers\Application\Mixes\UpdatePresetController;
use App\Http\Controllers\Application\Mixes\UpdateSelectedPresetController;
use App\Http\Controllers\Application\Mixes\DestroyPresetController;
use App\Http\Controllers\Application\Mixes\Voting\VoteSongController;

// Mix routes
Route::prefix('/mix')->name('mix.')->group(function () {
    // CRUD operations
    Route::post('/store', StoreMixController::class)->name('store');
    Route::post('/update/{mix}', UpdateMixController::class)->name('update');
    Route::delete('/destroy/{mix}', DestroyMixController::class)->name('destroy');

    // Songs management
    Route::post('/add-song/{mix}', AddSongToMixController::class)->name('add-song');
    Route::delete('/remove-song/{song}', RemoveSongFromMixController::class)->name('remove-song');
    Route::post('/get-mix-songs/{mix}', GetMixSongsController::class)->name('get-mix-songs');
    Route::post('/import-spotify-playlist/{mix}', ImportSpotifyPlaylistController::class)->name('import-spotify-playlist');

    // Access control
    Route::post('/generate-code/{mix}', GenerateMixCodeController::class)->name('generate-code');
    Route::get('/join/{session_code}', JoinMixController::class)->name('join');
    Route::post('/remove-session-code/{mix}', RemoveSessionCodeController::class)->name('remove-session-code');
    Route::post('/toggle-visibility/{mix}', ToggleMixIsPublicController::class)->name('toggle-visibility');

    // User management
    Route::post('/assign-co-dj/{mix}', AssignCoDJController::class)->name('assign-co-dj');
    Route::post('/remove-co-dj/{mix}', RemoveCoDJController::class)->name('remove-co-dj');
    Route::get('/search-user/{mix}', SearchUserController::class)->name('search-user');
    Route::post('/remove-user-access/{mix}', RemoveUserMixAccessController::class)->name('remove-user-access');
    Route::post('/change-permission/{mix}', UpdateMixUserPermissionsController::class)->name('change-permission');

    // Appearance
    Route::post('/update-theme/{mix}', UpdateMixThemeController::class)->name('update-theme');

    // Presets
    Route::prefix('/presets')->name('presets.')->group(function () {
        Route::post('/update/{preset}', UpdatePresetController::class)->name('update');
        Route::post('/update-selected/{mix}', UpdateSelectedPresetController::class)->name('update-selected');
        Route::delete('/destroy/{preset}', DestroyPresetController::class)->name('destroy');
    });

    // Voting
    Route::prefix('/voting')->name('voting.')->group(function () {
        Route::post('/vote/{queueSong}', VoteSongController::class)->name('vote');
    });
});

Route::get('/{mix:slug}/{tab?}', ShowMixController::class)->name('mix.show');
