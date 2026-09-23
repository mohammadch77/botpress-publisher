<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Demo Tenant',
                'plan' => 'pro',
                'status' => 'active',
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@botpress.test')],
            [
                'tenant_id' => $tenant->id,
                'name' => 'System Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'secret')),
                'status' => 'active',
            ]
        );

        $superAdminRole = Role::query()->where('tenant_id', null)->where('slug', 'super_admin')->first();

        if ($superAdminRole && ! $admin->roles->contains($superAdminRole->id)) {
            $admin->roles()->attach($superAdminRole->id, [
                'tenant_id' => $tenant->id,
                'assigned_at' => now(),
            ]);
        }
    }
}
