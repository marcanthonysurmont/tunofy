<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoteSongRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'queue_song_id' => ['required', 'integer', Rule::exists('queue_songs', 'id')],
            'vote_type' => ['required', 'string', Rule::in(['like', 'dislike', 'kill'])],
        ];
    }
}
