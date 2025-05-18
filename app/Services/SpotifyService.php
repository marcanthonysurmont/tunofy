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

            Log::info("Playing song with URI: {$uri} directly on device {$deviceId} for user {$user->id}");

            // Build the request body - minimize object creation
            $body = ['uris' => [$uri]];
            if ($positionMs !== null) {
                $body['position_ms'] = $positionMs;
            }

            // Single API call with all parameters
            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player/play',
                $body,
                ['device_id' => $deviceId]
            );

            // Simple success check
            if (!$response->successful() && $response->status() !== 404) {
                Log::error("Failed to play on device: " . $response->status());
            }

            return $response->successful();
        } catch (\Exception $e) {
            // Only log real errors
            if (!str_contains($e->getMessage(), '404')) {
                Log::error("Error playing song on device: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Resume playback on the user's active device
     */
    public function resumePlayback(User $user, ?string $deviceId = null): bool
    {
        try {
            // If deviceId is provided, use it directly
            $params = [];
            if ($deviceId) {
                $params['device_id'] = $deviceId;
            }

            // BUGFIX: Use an empty array instead of (object)[]
            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player/play',
                [], // Empty array, not (object)[]
                $params
            );

            return $response->successful();
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), '404')) {
                Log::error("Resume playback error: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Pause playback on the user's active device - simplified
     */
    public function pausePlayback(User $user, ?string $deviceId = null): bool
    {
        try {
            $params = [];
            if ($deviceId) {
                $params['device_id'] = $deviceId;
            }

            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player/pause',
                [],
                $params
            );

            return $response->successful();
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), '404')) {
                Log::error("Spotify pausePlayback error: " . $e->getMessage());
            }
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
            Log::info("Activating device {$deviceId} for user {$user->id}");

            // Transfer playback to the specified device - no verification needed
            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player',
                ['device_ids' => [$deviceId], 'play' => false]
            );

            // Simple success check - don't verify device activation
            if (!$response->successful()) {
                // Only log errors for non-404 responses
                if ($response->status() !== 404) {
                    Log::error("Failed to activate device: " . $response->status());
                }
                return false;
            }

            return true;
        } catch (\Exception $e) {
            // Only log real errors, not expected conditions
            if (!str_contains($e->getMessage(), '404')) {
                Log::error("Error activating device: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Activate a specific device - simplified version
     */
    public function activateSpecificDevice(User $user, string $deviceId): bool
    {
        try {
            Log::info("Activating device {$deviceId} for user {$user->id}");

            // OPTIMIZATION: Remove the usleep delay
            $response = $this->spotifyRequest(
                $user,
                'PUT',
                'https://api.spotify.com/v1/me/player',
                [
                    'device_ids' => [$deviceId],
                    'play' => false
                ]
            );

            return $response->successful();
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), '404')) {
                Log::error("Error activating device: " . $e->getMessage());
            }
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

    public function getPreviewUrl(string $trackId): string
    {
        try {
            $trackUrl = "https://open.spotify.com/track/" . $trackId;

            // Add a user agent to mimic a browser
            $html = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->get($trackUrl)->body();

            if (empty($html)) {
                Log::error("Empty HTML response for track {$trackId}");
                return '';
            }
            preg_match('/<meta\s+property="og:audio"\s+content="([^"]+)"/', $html, $matches);

            if (!empty($matches[1])) {
                return $matches[1];
            }

            Log::warning("No preview URL found for track {$trackId}");
            return '';
        } catch (\Exception $e) {
            Log::error("Error fetching track preview URL for {$trackId}: " . $e->getMessage());
            return '';
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

    public function getUserPlaylists(User $user): array
    {
        try {
            $response = $this->spotifyRequest(
                $user,
                'GET',
                'https://api.spotify.com/v1/me/playlists',
                [],
            );

            if (!$response->successful()) {
                Log::error("Failed to get Spotify playlists: " . $response->status());
                return [];
            }

            return $response->json()['items'] ?? [];
        } catch (\Exception $e) {
            Log::error("Error fetching Spotify playlists: " . $e->getMessage());
            return [];
        }
    }

    public function getPlaylistTracks(User $user, string $playlistId): array
    {
        try {
            $allTracks = [];
            $nextUrl = "https://api.spotify.com/v1/playlists/{$playlistId}/tracks";
            $pageCount = 0;

            // Loop until we have no more pages
            while ($nextUrl) {
                $pageCount++;
                Log::info("Fetching playlist tracks page {$pageCount}, URL: {$nextUrl}");

                if ($pageCount === 1) {
                    $response = $this->spotifyRequest(
                        $user,
                        'GET',
                        $nextUrl,
                        [],
                    );
                } else {
                    // For pagination URLs, use direct HTTP request
                    $accessToken = $this->getAccessToken($user);
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->get($nextUrl);

                    // Handle token expiration
                    if ($response->status() === 401) {
                        $this->refreshAccessToken($user);
                        $accessToken = $this->getAccessToken($user);
                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])->get($nextUrl);
                    }
                }

                if (!$response->successful()) {
                    Log::error("Failed to get Spotify playlist tracks: " . $response->status());
                    return $allTracks;
                }

                $responseData = $response->json();

                // Add tracks from this page to our collection
                if (isset($responseData['items']) && is_array($responseData['items'])) {
                    $allTracks = array_merge($allTracks, $responseData['items']);
                    Log::info("Added " . count($responseData['items']) . " tracks from page {$pageCount}");
                }

                // Get the next URL for pagination, or null if we're done
                $nextUrl = $responseData['next'] ?? null;
            }

            Log::info("Fetched " . count($allTracks) . " tracks from playlist {$playlistId} in {$pageCount} pages");
            return $allTracks;
        } catch (\Exception $e) {
            Log::error("Error fetching Spotify playlist tracks: " . $e->getMessage());
            return [];
        }
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
