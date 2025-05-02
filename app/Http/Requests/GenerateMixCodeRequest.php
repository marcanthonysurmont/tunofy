<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMixCodeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'session_code_permission' => ['required', 'string','in:view,contribute, edit',],
        ];
    }
}
