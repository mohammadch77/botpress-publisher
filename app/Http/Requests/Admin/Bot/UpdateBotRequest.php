<?php

namespace App\Http\Requests\Admin\Bot;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('bot')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'token' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:active,inactive,error,pending'],
        ];
    }
}
