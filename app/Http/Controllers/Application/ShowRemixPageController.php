<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ShowRemixPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('RemixPage', []);
    }
}
