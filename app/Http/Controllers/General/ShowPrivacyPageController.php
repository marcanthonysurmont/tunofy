<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ShowPrivacyPageController extends Controller
{
    public function __invoke(): View
    {
        return view('privacy', [
            'user' => Auth::user()
        ]);
    }
}
