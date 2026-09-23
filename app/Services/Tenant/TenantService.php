<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TenantService
{
    public function list(): Collection
    {
        return Tenant::query()->latest()->get();
    }

    public function find(string $uuid): Tenant
    {
        return Tenant::query()->where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): Tenant
    {
        return Tenant::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'plan' => $data['plan'] ?? 'free',
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->refresh();
    }

    public function delete(Tenant $tenant): void
    {
        $tenant->delete();
    }
}
