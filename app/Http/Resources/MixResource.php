<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MixResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'session_code' => $this->when($this->authorized['isOwner'] ?? false, $this->session_code),
            'session_code_expires_at' => $this->when($this->authorized['isOwner'] ?? false, $this->session_code_expires_at),
            'session_code_permission' => $this->when($this->authorized['isOwner'] ?? false, $this->session_code_permission),
            'is_public' => $this->is_public,
            'is_active' => $this->is_active,
            'co_dj_id' => $this->co_dj_id,
            'preset_id' => $this->preset_id,
            'avatar' => $this->avatar,
            'mix_count' => $this->mix_count,
            'songs' => SongResource::collection($this->whenLoaded('songs'))->jsonSerialize(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'authorized' => $this->authorized,
        ];
    }
}
