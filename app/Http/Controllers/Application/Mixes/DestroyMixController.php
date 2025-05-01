<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Models\Mix;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DestroyMixController extends Controller
{
    public function __invoke(Mix $mix): RedirectResponse
    {
        $mix->delete();

        return redirect()->back()
            ->with('success', 'Mix deleted successfully.');
    }
}
