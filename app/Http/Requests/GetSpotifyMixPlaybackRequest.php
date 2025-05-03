<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetSpotifyMixPlaybackRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mix_id' => ['required', Rule::exists('mixes', 'id')],
            'max_age' => ['nullable', 'integer']
        ];
    }
}
