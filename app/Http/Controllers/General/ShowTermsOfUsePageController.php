<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ShowTermsOfUsePageController extends Controller
{
    public function __invoke(): View
    {
        return view('termsofuse', [
            'user' => Auth::user()
        ]);
    }
}
