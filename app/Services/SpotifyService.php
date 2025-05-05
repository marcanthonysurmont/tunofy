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
            $response = $this->spotifyRequest(
                $user,
                'PUT', 
                'https://api.spotify.com/v1/me/player/play'
            );
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Spotify resumePlayback error: " . $e->getMessage());
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
