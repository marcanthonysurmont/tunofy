<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;

class SpotifyService
{
    public function search($query): array
    {
        $response = $this->spotifyRequest(Auth::user(), 'GET', 'https://api.spotify.com/v1/search', [
            'q' => $query,
            'type' => 'track',
            'limit' => 10,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json(['error' => 'Spotify API request failed'], $response->status());
    }

    public function playSong(User $user, string $uri): bool
    {
        try {
            // First check if there's active playback
            $currentPlayback = $this->getCurrentPlayback($user);

            // If no active playback, try to activate a device first
            if (!$currentPlayback) {
                Log::info("No active playback, attempting to activate device");
                $activated = $this->activateDevice($user);

                // Give Spotify a moment to register the device activation
                if ($activated) {
                    sleep(1);
                }
            }

            // Now try to play the song
            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player/play',
                ['uris' => [$uri]]
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Spotify playSong error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resume playback on the user's active device
     */
    public function resumePlayback(User $user): bool
    {
        try {
            // Get device info and activate if needed
            $currentPlayback = $this->getCurrentPlayback($user);
            if (!$currentPlayback && !$this->activateDevice($user)) {
                Log::error("No active device available for playback");
                return false;
            }

            // Build endpoint with device ID if available
            $endpoint = 'https://api.spotify.com/v1/me/player/play';
            if (isset($currentPlayback['device']['id'])) {
                $endpoint .= '?device_id=' . $currentPlayback['device']['id'];
            }

            // Make request with empty object body
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken($user),
                'Content-Type' => 'application/json'
            ])->put($endpoint, (object)[]);

            // Log result and return success status
            Log::info($response->successful()
                ? "Successfully resumed playback"
                : "Failed to resume playback: " . $response->body());

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Resume playback error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pause playback on the user's active device
     */
    public function pausePlayback(User $user): bool
    {
        try {
            Log::info("Pausing Spotify playback for user {$user->id}");

            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player/pause'
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Spotify pausePlayback error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current playback state from Spotify
     */
    public function getCurrentPlayback(User $user): ?array
    {
        try {
            Log::info("Requesting playback data from Spotify for user {$user->id}");

            // Get a fresh access token
            $accessToken = $this->getAccessToken($user);

            if (!$accessToken) {
                Log::error("Failed to get Spotify access token for user {$user->id}");
                return null;
            }

            // Make the API request using Http facade (not a spotifyClient)
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get('https://api.spotify.com/v1/me/player');

            // Check if the request was successful
            if (!$response->successful()) {
                Log::error("Spotify API returned error: " . $response->status());
                return null;
            }

            // For 204 No Content (no active playback)
            if ($response->status() === 204) {
                Log::info("No active playback for user {$user->id}");
                return null;
            }

            $data = $response->json();

            // Log data size to verify we're getting something
            $dataSize = is_array($data) ? count($data) : 0;
            Log::info("Received {$dataSize} fields in playback data");

            return $data;
        } catch (\Exception $e) {
            Log::error("Spotify API error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a valid access token for the user
     */
    public function getAccessToken($user): string
    {
        // Check if token is expired or about to expire
        if ($this->isTokenExpired($user)) {
            // Refresh token before making the request
            $this->refreshAccessToken($user);
        }

        return $user->access_token;
    }

    /**
     * Activate a Spotify device by transferring playback to it
     */
    public function activateDevice(User $user): bool
    {
        try {
            // 1. Get all available devices
            $devicesResponse = $this->spotifyRequest(
                $user,
                'GET',
                'https://api.spotify.com/v1/me/player/devices'
            );

            if (!$devicesResponse->successful()) {
                Log::error("Failed to get Spotify devices: " . $devicesResponse->status());
                return false;
            }

            $devices = $devicesResponse->json()['devices'] ?? [];

            if (empty($devices)) {
                Log::error("No Spotify devices found for user {$user->id}");
                return false;
            }

            // 2. Select the first available device
            $deviceId = $devices[0]['id'];

            // 3. Transfer playback to that device (this activates it)
            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player',
                [
                    'device_ids' => [$deviceId],
                    'play' => false // Don't start playback yet
                ]
            );

            $success = $response->successful();

            if ($success) {
                Log::info("Successfully activated Spotify device {$deviceId} for user {$user->id}");
            } else {
                Log::error("Failed to activate Spotify device: " . $response->status());
            }

            return $success;
        } catch (\Exception $e) {
            Log::error("Spotify device activation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's available Spotify devices
     */
    public function getUserDevices(User $user): array
    {
        try {
            $response = $this->spotifyRequest(
                $user,
                'GET',
                'https://api.spotify.com/v1/me/player/devices'
            );

            if (!$response->successful()) {
                Log::error("Failed to get Spotify devices: " . $response->status());
                return [];
            }

            return $response->json()['devices'] ?? [];
        } catch (\Exception $e) {
            Log::error("Error fetching Spotify devices: " . $e->getMessage());
            return [];
        }
    }

    public function setVolume(User $user, int $volumePercent): bool
    {
        try {
            // Ensure volume is within valid range
            $volumePercent = max(0, min(100, $volumePercent));

            Log::info("Setting Spotify volume to {$volumePercent}% for user {$user->id}");

            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player/volume',
                [
                    'query' => [
                        'volume_percent' => $volumePercent
                    ]
                ]
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Spotify setVolume error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Make a request to the Spotify API
     */
    protected function spotifyRequest(User $user, string $method, string $endpoint, array $body = [], array $queryParams = [], array $additionalHeaders = []): Response
    {
        // Add query parameters to URL if any exist
        if (!empty($queryParams)) {
            $endpoint .= (strpos($endpoint, '?') === false ? '?' : '&') . http_build_query($queryParams);
        }

        // Get access token
        $accessToken = $this->getAccessToken($user);

        // Build headers
        $headers = array_merge([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $additionalHeaders);

        // Make the request
        $response = Http::withHeaders($headers)->$method($endpoint, $body);

        // Handle token expiration
        if ($response->status() === 401) {
            // Refresh token and try again
            $this->refreshAccessToken($user);
            $accessToken = $this->getAccessToken($user);

            $headers['Authorization'] = 'Bearer ' . $accessToken;
            $response = Http::withHeaders($headers)->$method($endpoint, $body);
        }

        return $response;
    }

    /**
     * Check if the token is expired or about to expire (within 5 minutes)
     */
    private function isTokenExpired($user): bool
    {
        if (empty($user->token_expires_at)) {
            return true;
        }

        // Convert to Carbon if it's a string
        $expiresAt = $user->token_expires_at;
        if (is_string($expiresAt)) {
            $expiresAt = Carbon::parse($expiresAt);
        }

        // Check if expired or about to expire in the next 5 minutes
        return $expiresAt->subMinutes(5)->isPast();
    }

    private function refreshAccessToken($user): bool
    {
        $clientId = config('services.spotify.client_id');
        $clientSecret = config('services.spotify.client_secret');

        $response = Http::asForm()->withBasicAuth($clientId, $clientSecret)
            ->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $user->refresh_token,
            ]);

        if ($response->successful()) {
            $data = $response->json();

            $user->access_token = $data['access_token'];
            // Store expiration time
            $expiresIn = $data['expires_in'] ?? 3600;
            $user->token_expires_at = Carbon::now()->addSeconds($expiresIn);
            $user->save();

            return true;
        }

        return false;
    }
}
