<?php

namespace App\Http\Requests\Admin\Destination;

use Illuminate\Foundation\Http\FormRequest;

class AddDestinationUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('destination')) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permission' => ['sometimes', 'in:view,publish,manage'],
        ];
    }
}
