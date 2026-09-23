<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'plan' => ['sometimes', 'in:free,pro,enterprise'],
            'status' => ['sometimes', 'in:active,suspended,trial,cancelled'],
        ];
    }
}
