<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateMixCodeRequest;
use App\Models\Mix;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class GenerateMixCodeController extends Controller
{
    public function __invoke(GenerateMixCodeRequest $request, Mix $mix): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $code = Str::random(5);

            $mix->update([
                'session_code' => Str::upper($code),
                'session_code_permission' => $validated['session_code_permission'],
                'session_code_expires_at' => now()->addMinutes(30),
            ]);

            return redirect()->back()
                ->with(['success' => $mix->session_code]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to generate mix code');
        }
    }
}
