<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMixThemeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'theme_setting_definition_id' => ['required', Rule::exists('theme_setting_definitions', 'id')],
            'settings' => ['array'],
        ];
    }
}
