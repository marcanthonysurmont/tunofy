<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Support\Facades\Auth;

class JoinMixController extends Controller
{
    public function __invoke(string $sessionCode)
    {
        $mix = Mix::where('session_code', $sessionCode)
            ->where('session_code_expires_at', '>', now())
            ->first();

        if (!$mix) {
            return redirect()->back()
                ->with('danger', 'Invalid or expired mix code.');
        }

        Auth::user()->accessibleMixes()->attach($mix);

        return redirect()->route('mix.show', $mix->slug)
            ->with('success', 'You have joined the mix successfully.');
    }
}
