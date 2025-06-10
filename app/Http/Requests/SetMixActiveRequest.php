<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetMixActiveRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
            'reset_queue' => ['sometimes', 'boolean'],
            'deviceId' => ['sometimes', 'string', 'nullable',],
        ];
    }
}
