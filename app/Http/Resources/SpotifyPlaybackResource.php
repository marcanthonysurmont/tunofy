<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SpotifyPlaybackResource extends JsonResource
{
    public function toArray($request)
    {
        // Get the data from the resource
        $data = $this->resource;

        // Structure the data to match what your Vue component expects
        return [
            'is_playing' => $data['is_playing'] ?? false,
            'progress_ms' => $data['progress_ms'] ?? 0,
            // Use 'item' instead of 'track' to match your component's expectations
            'item' => isset($data['item']) ? [
                'id' => $data['item']['id'] ?? null,
                'name' => $data['item']['name'] ?? null,
                'uri' => $data['item']['uri'] ?? null,
                'duration_ms' => $data['item']['duration_ms'] ?? 0,
                'explicit' => $data['item']['explicit'] ?? false,
                'popularity' => $data['item']['popularity'] ?? 0,
                'artists' => collect($data['item']['artists'] ?? [])->map(function ($artist) {
                    return [
                        'id' => $artist['id'] ?? null,
                        'name' => $artist['name'] ?? null,
                        'uri' => $artist['uri'] ?? null,
                    ];
                })->toArray(),
                'album' => isset($data['item']['album']) ? [
                    'id' => $data['item']['album']['id'] ?? null,
                    'name' => $data['item']['album']['name'] ?? null,
                    'images' => $data['item']['album']['images'] ?? [],
                    'release_date' => $data['item']['album']['release_date'] ?? null,
                ] : null,
            ] : null,
            'timestamp' => $data['timestamp'] ?? null,
        ];
    }
}
