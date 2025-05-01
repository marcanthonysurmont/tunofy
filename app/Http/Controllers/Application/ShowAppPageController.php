<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class ShowAppPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('TestPage', [
            'your_mixes' => Auth::user()->mixes(),
        ]);
    }
}
