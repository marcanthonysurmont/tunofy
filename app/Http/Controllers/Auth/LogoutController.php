<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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

        $mainDomain = str_replace('app.', '', env('APP_URL'));
        
        return Inertia::location($mainDomain);
    }
}
