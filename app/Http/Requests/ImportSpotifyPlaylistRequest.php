<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportSpotifyPlaylistRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'playlist_id' => ['required', 'string'],
        ];
    }
}
