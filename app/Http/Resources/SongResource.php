<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SongResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mix_id' => $this->mix_id,
            'spotify_id' => $this->spotify_id,
            'user_id' => $this->user_id,
            'duration_ms' => $this->duration_ms,
            'last_fetched_at' => $this->last_fetched_at,
            'name' => $this->name,
            'artist' => $this->artist,
            'image_url' => $this->image_url,
            'user' => UserResource::make($this->whenLoaded('user'))->jsonSerialize(),
        ];
    }
}
