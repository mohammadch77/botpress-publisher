<?php

namespace App\Http\Requests\Admin\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Tenant::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['sometimes', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'plan' => ['sometimes', 'in:free,pro,enterprise'],
            'status' => ['sometimes', 'in:active,suspended,trial,cancelled'],
            'settings' => ['sometimes', 'array'],

            'admin_name' => ['required', 'string', 'max:191'],
            'admin_email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', 'min:8'],
        ];
    }
}
