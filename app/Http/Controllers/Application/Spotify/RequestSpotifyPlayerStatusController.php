<?php

namespace App\Http\Controllers\Application\Spotify;

use App\Http\Requests\RequestSpotifyPlayerStatusRequest;
use App\Http\Controllers\Controller;
use App\Models\Mix;
use Illuminate\Support\Facades\Log;

class RequestSpotifyPlayerStatusController extends Controller
{
    public function __invoke(RequestSpotifyPlayerStatusRequest $request)
    {
        $mixId = $request->input('mix_id');

        // Get a fresh instance from the database (ignoring cache)
        $mix = Mix::findOrFail($mixId);

        // Force a database refresh
        $mix->refresh();

        // Check if user is authorized to view this mix
        $this->authorize('view', $mix);

        // Log the request
        Log::info("Status request for mix {$mix->id}, current status: " .
            ($mix->is_active ? 'active' : 'inactive'));

        // Return the current status from database (not cache)
        return response()->json([
            'success' => true,
            'mix_id' => $mix->id,
            'is_active' => (bool) $mix->is_active,
            'timestamp' => now()->toIso8601String(),
            'source' => 'direct-db-check'
        ]);
    }
}
