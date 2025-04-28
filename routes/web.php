<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('/app', function () {
    return Inertia::render('TestPage');
});
