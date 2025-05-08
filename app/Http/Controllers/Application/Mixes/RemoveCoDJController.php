<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;
use App\Events\CoDJUpdatedEvent;

class RemoveCoDJController extends Controller
{
    public function __invoke(Mix $mix): RedirectResponse
    {
        $this->authorize('assignCoDJ', $mix);

        try {
            CoDJUpdatedEvent::dispatch($mix->coDJ);

            $mix->update(['co_dj_id' => null]);

            return redirect()->back()
                ->with('success', 'Co-DJ removed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('success', 'Co-DJ removed successfully.');
        }
    }
}
