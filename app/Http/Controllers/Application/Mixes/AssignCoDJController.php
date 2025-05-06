<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignCoDJRequest;
use App\Models\Mix;
use Illuminate\Http\RedirectResponse;

class AssignCoDJController extends Controller
{
    public function __invoke(AssignCoDJRequest $request, Mix $mix): RedirectResponse
    {
        $this->authorize('assignCoDJ', $mix);

        $validated = $request->validated();

        try {
            $mix->update(['co_dj_id' => $validated['user_id']]);

            return redirect()->back()
                ->with('success', 'Co-DJ assigned successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('success', 'Co-DJ assigned successfully.');
        }
    }
}