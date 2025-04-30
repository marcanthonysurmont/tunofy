<?php

namespace App\Http\Controllers\General;

use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;

class ShowLandingPageController extends Controller
{
    public function __invoke(): View
    {
        return view('landing');
    }
}
