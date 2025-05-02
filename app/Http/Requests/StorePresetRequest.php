<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePresetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'batch_size' => ['required', 'integer', 'min:1'],
            'max_songs' => ['integer', 'min:1'],
            'num_rounds' => ['integer', 'min:1'],
            'requires_approval' => ['required', 'boolean'],
            'voting_enabled' => ['required', 'boolean'],
            'kill_percentage_percent' => ['required', 'integer', 'between:0,100'],
            'priority_boost_new' => ['required', 'boolean'],
            'auto_remove_negative' => ['required', 'boolean'],
            'emoji_chat_enabled' => ['required', 'boolean'],
        ];
    }
}
