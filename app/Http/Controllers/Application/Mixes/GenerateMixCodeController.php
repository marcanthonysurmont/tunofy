<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateMixCodeRequest;
use App\Models\Mix;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateMixCodeController extends Controller
{
    public function __invoke(GenerateMixCodeRequest $request, Mix $mix): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $normalCode = Str::random(5);
            $code = Str::upper($normalCode);

            $mix->update([
                'session_code' => $code,
                'session_code_permission' => $validated['session_code_permission'],
                'session_code_expires_at' => now()->addMinutes(30),
            ]);

            $qrCode = QrCode::size(300)->generate(
                route('mix.join', ['session_code' => $code])
            );

            return redirect()->back()
                ->with([
                    'success' => $mix->session_code, 
                    'qr_code' => $qrCode
                ]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to generate mix code');
        }
    }
}
