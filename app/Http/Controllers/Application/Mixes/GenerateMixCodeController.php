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

        $code = Str::random(5);

        $mix->session_code = Str::upper($code);
        $mix->session_code_permission = $validated['session_code_permission'];
        $mix->session_code_expires_at = now()->addMinutes(30);
        $mix->save();

        return redirect()->back()->with([
            'success' => $mix->session_code,
        ]);
    }
}
