<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SpotifySearchResource extends JsonResource
{
    public function toArray($request)
    {
        // Get the data from the resource
        $data = $this->resource;

        // Structure search results to match the same format as playback data
        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? null,
            'uri' => $data['uri'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? 0,
            'explicit' => $data['explicit'] ?? false,
            'popularity' => $data['popularity'] ?? 0,
            'artists' => collect($data['artists'] ?? [])->map(function ($artist) {
                return [
                    'id' => $artist['id'] ?? null,
                    'name' => $artist['name'] ?? null,
                    'uri' => $artist['uri'] ?? null,
                ];
            })->toArray(),
            'album' => isset($data['album']) ? [
                'id' => $data['album']['id'] ?? null,
                'name' => $data['album']['name'] ?? null,
                'images' => $data['album']['images'] ?? [],
                'release_date' => $data['album']['release_date'] ?? null,
            ] : null,
        ];
    }
}
