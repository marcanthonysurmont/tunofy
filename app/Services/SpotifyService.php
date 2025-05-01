<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class SpotifyService
{
    public function search($query)
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

    private function spotifyRequest($user, $method, $url, $query = [])
    {
        $response = Http::withToken($user->access_token)->$method($url, $query);

        if ($response->status() === 401) {
            // Token expired, try to refresh
            $refreshed = $this->refreshAccessToken($user);

            if ($refreshed) {
                // Try the request again after refreshing the token
                return Http::withToken($user->access_token)->$method($url, $query);
            }

            // If token refresh failed or other issues, throw an exception
            throw new \Exception('Unable to refresh access token');
        }

        // Return the response object, not just the JSON data
        return $response;
    }

    private function refreshAccessToken($user)
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
            $user->save();

            return true;
        }

        return false;
    }
}
