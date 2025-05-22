<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DeleteAccountController extends Controller
{
    public function __invoke(DeleteAccountRequest $request)
    {
        $validated = $request->validated();

        if ($validated['input'] === 'CONFIRM') {
            Auth::user()->delete();
            Auth::logout();

            $request->session()->regenerateToken();

            return Inertia::location(env('APP_URL'));
        }

        return redirect()->back()
            ->with('error', 'Invalid confirmation input. Please enter "CONFIRM" to delete your account.');
    }
}
