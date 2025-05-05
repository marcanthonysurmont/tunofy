<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSelectedPresetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'preset_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
