<?php

use App\Http\Controllers\Application\Spotify\GetDevicesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LogoutController;

use App\Http\Controllers\General\ShowDPAPageController;
use App\Http\Controllers\Auth\SpotifyCallbackController;
use App\Http\Controllers\Auth\RedirectToSpotifyController;
use App\Http\Controllers\Application\ShowAppPageController;

use App\Http\Controllers\General\ShowLandingPageController;
use App\Http\Controllers\General\ShowPrivacyPageController;

use App\Http\Controllers\Application\Mixes\JoinMixController;

use App\Http\Controllers\Application\Mixes\ShowMixController;
use App\Http\Controllers\Application\Mixes\StoreMixController;
use App\Http\Controllers\General\ShowTermsOfUsePageController;
use App\Http\Controllers\Application\Mixes\UpdateMixController;
use App\Http\Controllers\Application\Mixes\DestroyMixController;
use App\Http\Controllers\Application\Mixes\AddSongToMixController;
use App\Http\Controllers\Application\Mixes\UpdatePresetController;
use App\Http\Controllers\Application\Spotify\SearchSongController;
use App\Http\Controllers\Application\Mixes\DestroyPresetController;
use App\Http\Controllers\Application\Spotify\SetMixActiveController;
use App\Http\Controllers\Application\Spotify\GetTrackPreviewController;
use App\Http\Controllers\Application\Mixes\GenerateMixCodeController;
use App\Http\Controllers\Application\Mixes\RemoveSongFromMixController;
use App\Http\Controllers\Application\Mixes\RemoveSessionCodeController;

use App\Http\Controllers\Application\Mixes\UpdateSelectedPresetController;
use App\Http\Controllers\Application\Spotify\RequestSpotifyPlayerStatusController;
use App\Http\Controllers\Application\Spotify\PauseMixPlaybackController;
use App\Http\Controllers\Application\Spotify\ResumeMixPlaybackController;
use App\Http\Controllers\Application\Spotify\PlayNextSongController;
use App\Http\Controllers\Application\Spotify\PlayPreviousSongController;
use App\Http\Controllers\Application\Spotify\TransferPlaybackController;
use App\Http\Controllers\Application\Spotify\GetSpotifyPlaylistsController;

use App\Http\Controllers\Application\Mixes\AssignCoDJController;
use App\Http\Controllers\Application\Mixes\RemoveCoDJController;
use App\Http\Controllers\Application\Mixes\ToggleMixIsPublicController;
use App\Http\Controllers\Application\Mixes\SearchUserController;
use App\Http\Controllers\Application\Mixes\RemoveUserMixAccessController;
use App\Http\Controllers\Application\Mixes\ImportSpotifyPlaylistController;
use App\Http\Controllers\Application\Mixes\UpdateMixThemeController;

Route::domain('app.' . parse_url(env('APP_URL'), PHP_URL_HOST))->group(function () {
    Route::middleware('auth')->group(function () {
        Route::prefix('/mix')->name('mix.')->group(function () {
            Route::post('/store', StoreMixController::class)->name('store');
            Route::post('/update/{mix}', UpdateMixController::class)->name('update');
            Route::delete('/destroy/{mix}', DestroyMixController::class)->name('destroy');
            Route::post('/add-song/{mix}', AddSongToMixController::class)->name('add-song');
            Route::delete('/remove-song/{song}', RemoveSongFromMixController::class)->name('remove-song');
            Route::post('/generate-code/{mix}', GenerateMixCodeController::class)->name('generate-code');
            Route::post('/join/{session_code}', JoinMixController::class)->name('join');
            Route::post('/remove-session-code/{mix}', RemoveSessionCodeController::class)->name('remove-session-code');
            Route::post('/assign-co-dj/{mix}', AssignCoDJController::class)->name('assign-co-dj');
            Route::post('/remove-co-dj/{mix}', RemoveCoDJController::class)->name('remove-co-dj');
            Route::post('/toggle-visibility/{mix}', ToggleMixIsPublicController::class)->name('toggle-visibility');
            Route::get('/search-user/{mix}', SearchUserController::class)->name('search-user');
            Route::post('/remove-user-access/{mix}', RemoveUserMixAccessController::class)->name('remove-user-access');
            Route::post('/import-spotify-playlist/{mix}', ImportSpotifyPlaylistController::class)->name('import-spotify-playlist');
            Route::post('/update-theme/{mix}', UpdateMixThemeController::class)->name('update-theme');

            // app/mix/presets (mix.presets)
            Route::prefix('/presets')->name('presets.')->group(function () {
                Route::post('/update/{preset}', UpdatePresetController::class)->name('update');
                Route::post('/update-selected/{mix}', UpdateSelectedPresetController::class)->name('update-selected');
                Route::delete('/destroy/{preset}', DestroyPresetController::class)->name('destroy');
            });

        });

        Route::prefix('api/spotify')->name('api.spotify.')->group(function () {
            Route::post('/search', SearchSongController::class)->name('search');
            Route::post('/track-preview', GetTrackPreviewController::class)->name('track-preview');
            Route::post('/get-playlists', GetSpotifyPlaylistsController::class)->name('get-playlist');

            Route::get('/request-status', RequestSpotifyPlayerStatusController::class)->name('request-status');
            Route::post('/set-mix-active/{mix}', SetMixActiveController::class)->name('set-mix-active');
            Route::post('/pause-mix/{mix}', PauseMixPlaybackController::class)->name('pause-mix');
            Route::post('/resume-mix/{mix}', ResumeMixPlaybackController::class)->name('resume-mix');
            Route::post('/skip-song/{mix}', PlayNextSongController::class)->name('skip-song');
            Route::post('/previous-song/{mix}', PlayPreviousSongController::class)->name('previous-song');
            Route::get('/devices', GetDevicesController::class)->name('devices');
            Route::post('/transfer-playback/{mix}', TransferPlaybackController::class)->name('transfer-playback');
        });

        Route::get('/', ShowAppPageController::class)->name('app');
        Route::get('/{mix:slug}/{tab?}', ShowMixController::class)->name('mix.show');
        Route::post('/logout', LogoutController::class)->name('logout');
    });

    Route::middleware('guest')->group(function () {
        Route::prefix('/auth')->group(function () {
            Route::get('/login/spotify', RedirectToSpotifyController::class)->name('login');
            Route::get('/spotify/callback', SpotifyCallbackController::class)->name('callback');
        });
    });
});

Route::get('/', ShowLandingPageController::class)->name('landing');
Route::get('/dpa', ShowDPAPageController::class);
Route::get('/privacy', ShowPrivacyPageController::class);
Route::get('/terms-of-use', ShowTermsOfUsePageController::class);