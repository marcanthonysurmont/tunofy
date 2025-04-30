<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ShowTermsOfUsePageController extends Controller
{
    public function __invoke(): View
    {
        return view('termsofuse');
    }
}
