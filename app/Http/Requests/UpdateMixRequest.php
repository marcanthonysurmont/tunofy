<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMixRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_public' => ['required', 'boolean'],
            // 'preset_id' => ['nullable', Rule::exists('presets', 'id')],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
