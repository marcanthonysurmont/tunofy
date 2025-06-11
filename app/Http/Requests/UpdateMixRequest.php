<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMixRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            // 'is_public' => ['required', 'boolean'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
