<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Models\MixAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class JoinMixController extends Controller
{
    public function __invoke(string $sessionCode): RedirectResponse
    {
        $mix = Mix::validSessionCode($sessionCode)->first();

        if (!$mix) {
            return redirect()->back()
                ->with('danger', 'Invalid or expired mix code.');
        }

        if ($mix->hasUserJoined(Auth::id())) {
            return redirect()->back()
                ->with('danger', 'You have already joined this mix.');
        }

        try {
            MixAccess::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'mix_id' => $mix->id,
                ],
                [
                    'permission' => $mix->session_code_permission,
                ]
            );
    
            return redirect()->route('mix.show', $mix->slug)
                ->with('success', 'You have joined the mix successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to join the mix');
        }
    }
}
