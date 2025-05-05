<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Models\Mix;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DestroyMixController extends Controller
{
    public function __invoke(Mix $mix): RedirectResponse
    {
        $this->authorize('delete', $mix);

        try {
            $mix->delete();

            return redirect()
                ->route('app')
                ->with('success', 'Mix deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to delete mix. Please try again later.');
        }
    }
}
