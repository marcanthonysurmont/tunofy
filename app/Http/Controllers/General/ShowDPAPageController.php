<?php

namespace App\Http\Controllers\General;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ShowDPAPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dpa');
    }
}
