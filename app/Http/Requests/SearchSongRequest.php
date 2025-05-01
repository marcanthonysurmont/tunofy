<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchSongRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:255'],
        ];
    }
}
