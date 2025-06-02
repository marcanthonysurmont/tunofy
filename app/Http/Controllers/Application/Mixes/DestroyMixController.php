<?php

namespace App\Http\Controllers\Application\Mixes;

use Exception;
use App\Models\Mix;
use App\Events\MixDeletedEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class DestroyMixController extends Controller
{
    public function __invoke(Mix $mix): RedirectResponse
    {
        $this->authorize('delete', $mix);

        try {
            $avatarPath = $mix->avatar;

            if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }

            $mix->delete();

            MixDeletedEvent::dispatch($mix);

            return redirect()->route('app')
                ->with('success', 'Mix deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('danger', 'Failed to delete mix. Please try again later.');
        }
    }
}
