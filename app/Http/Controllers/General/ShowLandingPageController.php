<?php

namespace App\Http\Controllers\General;

use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ShowLandingPageController extends Controller
{
    public function __invoke(): View
    {
        return view('landing', [
            'user' => Auth::user()
        ]);
    }
}
