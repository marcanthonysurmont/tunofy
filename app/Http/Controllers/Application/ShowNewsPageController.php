<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class ShowNewsPageController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();
        return Inertia::render('NewsSubPage', [
            'your_mixes' => fn () => $user->mixes,
            'joined_mixes' => fn () => $user->accessibleMixes,
        ]);
    }
}
