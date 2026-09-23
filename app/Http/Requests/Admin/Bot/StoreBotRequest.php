<?php

namespace App\Http\Requests\Admin\Bot;

use App\Models\Bot;
use Illuminate\Foundation\Http\FormRequest;

class StoreBotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Bot::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'in:telegram,bale'],
            'name' => ['required', 'string', 'max:191'],
            'token' => ['required', 'string'],
        ];
    }
}
