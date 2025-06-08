<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Application\ShowAppPageController;
use App\Http\Controllers\Application\ShowSettingsPageController;
use App\Http\Controllers\Application\ShowNewsPageController;
use App\Http\Controllers\Application\ShowRemixPageController;

// Main app pages
Route::get('/', ShowAppPageController::class)->name('app');
Route::get('/news', ShowNewsPageController::class)->name('news');
Route::get('/settings', ShowSettingsPageController::class)->name('settings');
Route::get('/remix', ShowRemixPageController::class)->name('remix');
