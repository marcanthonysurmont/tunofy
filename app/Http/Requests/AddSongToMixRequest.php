<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddSongToMixRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'spotify_id' => ['required', 'string'],
            'duration_ms' => ['required', 'integer'],
            'name' => ['required', 'string'],
            'artist' => ['required', 'string'],
            'image_url' => ['required', 'string'],
        ];
    }
}
