<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;

class ToggleMixIsPublicController extends Controller
{
    public function __invoke(Mix $mix): RedirectResponse
    {
        try {
            $isNowPublic = !$mix->is_public;
            $mix->update(['is_public' => $isNowPublic]);

            $message = 'Mix is now private.';

            //check if the mix is now public
            if ($isNowPublic === true) {
                $message = 'Mix is now public.';
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to apply changes. Please try again later.');
        }
    }
}
