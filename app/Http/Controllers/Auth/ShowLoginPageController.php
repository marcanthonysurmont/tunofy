<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ShowLoginPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('LoginPage');
    }
}
