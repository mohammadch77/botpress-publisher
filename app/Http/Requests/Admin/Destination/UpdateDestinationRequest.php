<?php

namespace App\Http\Requests\Admin\Destination;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('destination')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'status' => ['sometimes', 'in:active,inactive,error,pending'],
            'url' => ['sometimes', 'string', 'max:500'],
            'api_key' => ['sometimes', 'string'],
        ];
    }
}
