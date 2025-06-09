<?php

namespace App\Http\Controllers\Application\Mixes;

use App\Http\Controllers\Controller;
use App\Models\Mix;
use App\Models\MixAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\UserAccessUpdatedEvent;
use Illuminate\Support\Facades\Log;

class JoinMixController extends Controller
{
    public function __invoke(Request $request, string $sessionCode)
    {
        $mix = Mix::validSessionCode($sessionCode)->first();
        $user = Auth::user();

        // Check conditions and return error responses
        if (!$mix) {
            return response()->json([
                'errors' => ['code' => 'Invalid or expired mix code.']
            ], 422);
        }

        if ($mix->hasUserJoined($user->id)) {
            return response()->json([
                'errors' => ['code' => 'You have already joined this mix.']
            ], 422);
        }

        if ($mix->user_id === $user->id) {
            return response()->json([
                'errors' => ['code' => 'You cannot join your own mix.']
            ], 422);
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

            UserAccessUpdatedEvent::dispatch($mix);

            // Return success with redirect URL
            return response()->json([
                'success' => true,
                'message' => 'Successfully joined mix!',
                'redirect' => route('mix.show', $mix->slug)
            ]);

        } catch (\Exception $e) {
            Log::error('Error joining mix: ' . $e->getMessage());

            return response()->json([
                'errors' => ['code' => 'Failed to join the mix']
            ], 422);
        }
    }
}
