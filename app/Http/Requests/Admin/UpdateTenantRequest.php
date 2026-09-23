<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'slug' => ['sometimes', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant)],
            'plan' => ['sometimes', 'in:free,pro,enterprise'],
            'status' => ['sometimes', 'in:active,suspended,trial,cancelled'],
        ];
    }
}
