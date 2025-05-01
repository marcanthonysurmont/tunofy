<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Models\Mix;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ShowMixController extends Controller
{
    public function __invoke(Mix $mix): Response
    {
        return Inertia::render('TestPage', [
            'mix' => $mix,
            'songs' => $mix->songs(),
        ]);

    }
}
