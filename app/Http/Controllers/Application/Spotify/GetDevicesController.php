<?php

namespace App\Http\Controllers\Application\Spotify;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\SpotifyService;
use Illuminate\Support\Facades\Auth;

class GetDevicesController extends Controller
{
    public function __invoke(SpotifyService $spotifyService): JsonResponse
    {
        $devices = $spotifyService->getUserDevices(Auth::user());

        return response()->json([
            'devices' => $devices,
        ]);
    }
}
