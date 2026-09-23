<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'content.create', 'group' => 'content'],
            ['name' => 'content.edit', 'group' => 'content'],
            ['name' => 'content.delete', 'group' => 'content'],
            ['name' => 'content.view', 'group' => 'content'],

            ['name' => 'publication.create', 'group' => 'publication'],
            ['name' => 'publication.schedule', 'group' => 'publication'],
            ['name' => 'publication.cancel', 'group' => 'publication'],

            ['name' => 'destination.view', 'group' => 'destination'],
            ['name' => 'destination.manage', 'group' => 'destination'],

            ['name' => 'admin.access', 'group' => 'admin'],
            ['name' => 'admin.users', 'group' => 'admin'],
            ['name' => 'admin.settings', 'group' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $allPermissionIds = Permission::query()->pluck('id');

        $superAdmin = Role::query()->updateOrCreate(
            ['tenant_id' => null, 'slug' => 'super_admin'],
            ['name' => 'Super Admin', 'is_system' => true]
        );
        $superAdmin->permissions()->sync($allPermissionIds);

        $systemRoles = [
            'admin' => 'Admin',
            'editor' => 'Editor',
            'publisher' => 'Publisher',
        ];

        foreach ($systemRoles as $slug => $name) {
            Role::query()->updateOrCreate(
                ['tenant_id' => null, 'slug' => $slug],
                ['name' => $name, 'is_system' => true]
            );
        }
    }
}
