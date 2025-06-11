<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class ShowSettingsPageController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();
        return Inertia::render('UserSettingsPage', [
            'your_mixes' => fn () => $user->mixes,
            'joined_mixes' => fn () => $user->accessibleMixes,
        ]);
    }
}
