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

    /**
     * Play a song
     */
    public function playSong(User $user, string $trackId, ?string $deviceId = null): bool
    {
        try {
            // Ensure the URI is properly formatted
            $uri = $trackId;
            if (!str_starts_with($trackId, 'spotify:track:')) {
                $uri = 'spotify:track:' . $trackId;
            }

            Log::info("Playing song with URI: {$uri} for user {$user->id}" . ($deviceId ? " on device {$deviceId}" : ""));

            $endpoint = 'https://api.spotify.com/v1/me/player/play';
            $queryParams = [];

            // Add device ID to query params if provided
            if ($deviceId) {
                $queryParams['device_id'] = $deviceId;
            }

            $response = $this->spotifyRequest(
                $user,
                'PUT',
                $endpoint,
                ['uris' => [$uri]],
                $queryParams
            );

            $success = $response->successful();

            if (!$success) {
                Log::error("Failed to play song: " . $response->status() . " - " . $response->body());
            }

            return $success;
        } catch (\Exception $e) {
            Log::error("Error playing song: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Play a track directly on a specific device, with position support
     */
    public function playTrackOnDevice(User $user, string $trackId, string $deviceId, ?int $positionMs = null): bool
    {
        try {
            // Format the URI properly
            $uri = $trackId;
            if (!str_starts_with($trackId, 'spotify:track:')) {
                $uri = 'spotify:track:' . $trackId;
            }

            Log::info("Playing song with URI: {$uri} directly on device {$deviceId} for user {$user->id}" .
                     ($positionMs !== null ? " at position {$positionMs}ms" : ""));

            // Build the request body
            $body = ['uris' => [$uri]];

            // Add position_ms if provided
            if ($positionMs !== null) {
                $body['position_ms'] = $positionMs;
            }

            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player/play',
                $body,
                ['device_id' => $deviceId]
            );

            if (!$response->successful()) {
                Log::error("Failed to play on device: " . $response->status() . " - " . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Error playing song on device: " . $e->getMessage());
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
    public function activateDevice(User $user, ?string $deviceId = null): bool
    {
        try {
            if (!$deviceId) {
                // If no device ID is provided, try to use any available device
                $devices = $this->getDevices($user);
                if (empty($devices)) {
                    Log::error("No devices available for activation");
                    return false;
                }

                // Use the first active device or the first available device
                $activeDevice = collect($devices)->firstWhere('is_active', true);
                $deviceId = $activeDevice ? $activeDevice['id'] : $devices[0]['id'];
            }

            Log::info("Explicitly activating device {$deviceId} for user {$user->id}");

            // Transfer playback to the specified device
            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player',
                ['device_ids' => [$deviceId], 'play' => false]
            );

            if (!$response->successful()) {
                Log::error("Failed to activate device: " . $response->status() . " - " . $response->body());
                return false;
            }

            // Verify device is now active
            $retries = 0;
            while ($retries < 3) {
                $playbackData = $this->getCurrentPlayback($user);
                if ($playbackData && isset($playbackData['device']['id']) && $playbackData['device']['id'] === $deviceId) {
                    Log::info("Successfully verified device {$deviceId} is now active for user {$user->id}");
                    return true;
                }
                $retries++;
                sleep(0.5);
            }

            Log::info("Successfully activated Spotify device {$deviceId} for user {$user->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error activating device: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activate a specific device and wait for it to become active
     */
    public function activateSpecificDevice(User $user, string $deviceId): bool
    {
        try {
            Log::info("Explicitly activating device {$deviceId} for user {$user->id}");

            $endpoint = 'https://api.spotify.com/v1/me/player';
            $body = [
                'device_ids' => [$deviceId],
                'play' => false // Don't start playback yet
            ];

            $response = $this->spotifyRequest(
                $user,
                'PUT',
                $endpoint,
                $body
            );

            $success = $response->successful();

            if (!$success) {
                Log::error("Failed to activate device: " . $response->status() . " - " . $response->body());
            } else {
                // Short delay to ensure device activation takes effect
                // Slightly increased for desktop clients which need more time
                usleep(800000); // 800ms

                // Verify device is now active by checking player state
                $currentDevice = $this->getCurrentDevice($user);
                if ($currentDevice && $currentDevice['id'] === $deviceId) {
                    Log::info("Successfully verified device {$deviceId} is now active for user {$user->id}");
                } else {
                    Log::warning("Device activation may not have completed - proceeding anyway");
                }

                Log::info("Successfully activated Spotify device {$deviceId} for user {$user->id}");
            }

            return $success;
        } catch (\Exception $e) {
            Log::error("Error activating device: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the current active device
     */
    private function getCurrentDevice(User $user): ?array
    {
        try {
            $response = $this->spotifyRequest(
                $user,
                'GET',
                'https://api.spotify.com/v1/me/player'
            );

            if ($response->successful() && !empty($response->json())) {
                $data = $response->json();
                return $data['device'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error getting current device: " . $e->getMessage());
            return null;
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
