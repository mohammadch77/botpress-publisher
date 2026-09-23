<?php

namespace App\Services\Tenant;

use App\Enums\UserStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Tenant::query()->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find(string $uuid): Tenant
    {
        return Tenant::query()->where('uuid', $uuid)->firstOrFail();
    }

    public function withStats(Tenant $tenant): Tenant
    {
        $tenant->loadCount(['users', 'bots', 'publications']);
        $tenant->stats_users = $tenant->users_count;
        $tenant->stats_bots = $tenant->bots_count;
        $tenant->stats_publications = $tenant->publications_count;

        return $tenant;
    }

    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'plan' => $data['plan'] ?? 'free',
                'status' => $data['status'] ?? 'active',
                'settings' => $data['settings'] ?? null,
            ]);

            User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'status' => UserStatus::Active,
            ]);

            return $tenant;
        });
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
