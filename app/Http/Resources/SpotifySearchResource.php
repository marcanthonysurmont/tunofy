<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpotifySearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'name' => $this->resource['name'] ?? null,
            'popularity' => $this->resource['popularity'] ?? null,
            'explicit' => $this->resource['explicit'] ?? false,
            'duration_ms' => $this->resource['duration_ms'] ?? null,
            'external_url' => $this->resource['external_urls']['spotify'] ?? null,
            'preview_url' => $this->resource['preview_url'] ?? null,
            'uri' => $this->resource['uri'] ?? null,
            'is_playable' => $this->resource['is_playable'] ?? true,
            'artists' => collect($this->resource['artists'] ?? [])->map(function ($artist) {
                return [
                    'id' => $artist['id'] ?? null,
                    'name' => $artist['name'] ?? null,
                    'external_url' => $artist['external_urls']['spotify'] ?? null,
                ];
            }),
            'album' => [
                'name' => $this->resource['album']['name'] ?? null,
                'external_url' => $this->resource['album']['external_urls']['spotify'] ?? null,
                'images' => $this->resource['album']['images'] ?? [],
            ],
        ];
    }
}
