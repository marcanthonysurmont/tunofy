<?php

namespace App\Http\Controllers\Auth;

use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->session()->put('just_logged_out', true);

        Auth::logout();

        $request->session()->regenerateToken();

        return Inertia::location(route('landing'));
    }
}
