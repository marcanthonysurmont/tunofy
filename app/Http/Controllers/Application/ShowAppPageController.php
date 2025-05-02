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
        $user = Auth::user();

        return Inertia::render('MainAppPage', [
            'your_mixes' => $user->mixes,
            'joined_mixes' => $user->accessibleMixes
        ]);
    }
}
