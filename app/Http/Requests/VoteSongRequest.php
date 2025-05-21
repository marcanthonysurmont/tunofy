<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoteSongRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'vote_type' => ['required', 'string', Rule::in(['like', 'dislike', 'kill'])],
        ];
    }
}
