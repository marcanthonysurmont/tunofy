<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMixSongsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['required', 'integer']
        ];
    }
}
