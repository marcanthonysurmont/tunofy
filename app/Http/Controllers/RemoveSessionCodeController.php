<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;

class RemoveSessionCodeController extends Controller
{
    public function __invoke(Mix $mix): RedirectResponse
    {
        try {
            $mix->update([
                'session_code' => null,
                'session_code_expires_at' => null,
            ]);

            return redirect()->back()
                ->with('success', 'Session code removed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to remove session code. Please try again.');
        }
    }
}
