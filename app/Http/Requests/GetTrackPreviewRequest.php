<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTrackPreviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'track_id' => ['required', 'string'],
        ];
    }
}
