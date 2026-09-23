<?php

namespace App\Services\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with('roles')->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find(string $uuid): User
    {
        return User::query()->with('roles')->where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): User
    {
        $user = User::query()->create([
            'tenant_id' => $data['tenant_id'] ?? (app()->bound('current.tenant.id') ? app('current.tenant.id') : null),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => $data['status'] ?? 'active',
        ]);

        if (! empty($data['role_ids'])) {
            foreach ($data['role_ids'] as $roleId) {
                $this->assignRole($user, $roleId);
            }
        }

        return $user->load('roles');
    }

    public function update(User $user, array $data): User
    {
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update(collect($data)->only(['name', 'email', 'password', 'status'])->toArray());

        return $user->refresh()->load('roles');
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function assignRole(User $user, int $roleId, ?int $assignedBy = null): User
    {
        $role = Role::query()->findOrFail($roleId);

        $user->roles()->syncWithoutDetaching([
            $role->id => [
                'tenant_id' => $user->tenant_id,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ],
        ]);

        return $user->load('roles');
    }

    public function removeRole(User $user, int $roleId): User
    {
        $user->roles()->detach($roleId);

        return $user->load('roles');
    }
}
