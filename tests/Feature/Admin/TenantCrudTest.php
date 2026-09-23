<?php

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;

function makeSuperAdmin(Tenant $tenant): User
{
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $role = Role::query()->firstOrCreate(
        ['tenant_id' => null, 'slug' => 'super_admin'],
        ['name' => 'Super Admin', 'is_system' => true]
    );

    $user->roles()->attach($role->id, ['tenant_id' => $tenant->id, 'assigned_at' => now()]);

    return $user->fresh();
}

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
    $this->tenantB = Tenant::factory()->create(['name' => 'Tenant B']);
    $this->superAdmin = makeSuperAdmin($this->tenantA);
    $this->userA = User::factory()->create(['tenant_id' => $this->tenantA->id]);
});

it('lists tenants for a super admin', function () {
    $response = $this->actingAs($this->superAdmin)->getJson('/api/admin/tenants');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('forbids a regular user from listing tenants', function () {
    $response = $this->actingAs($this->userA)->getJson('/api/admin/tenants');

    $response->assertForbidden();
});

it('forbids a regular user from viewing another tenant', function () {
    $response = $this->actingAs($this->userA)->getJson("/api/admin/tenants/{$this->tenantB->uuid}");

    $response->assertForbidden();
});

it('allows a regular user to view their own tenant', function () {
    $response = $this->actingAs($this->userA)->getJson("/api/admin/tenants/{$this->tenantA->uuid}");

    $response->assertOk()->assertJsonPath('data.uuid', $this->tenantA->uuid);
});

it('shows tenant stats for a super admin', function () {
    $response = $this->actingAs($this->superAdmin)->getJson("/api/admin/tenants/{$this->tenantA->uuid}");

    $response->assertOk()
        ->assertJsonPath('data.uuid', $this->tenantA->uuid)
        ->assertJsonStructure(['data' => ['stats' => ['users', 'bots', 'publications']]]);
});

it('creates a tenant with an initial admin user', function () {
    $response = $this->actingAs($this->superAdmin)->postJson('/api/admin/tenants', [
        'name' => 'New Tenant',
        'admin_name' => 'New Admin',
        'admin_email' => 'new-admin@example.test',
        'admin_password' => 'password123',
    ]);

    $response->assertCreated()->assertJsonPath('data.name', 'New Tenant');

    $tenant = Tenant::query()->where('slug', 'new-tenant')->firstOrFail();
    expect(User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('email', 'new-admin@example.test')->exists())->toBeTrue();
});

it('updates a tenant', function () {
    $response = $this->actingAs($this->superAdmin)->putJson("/api/admin/tenants/{$this->tenantA->uuid}", [
        'name' => 'Renamed Tenant',
    ]);

    $response->assertOk()->assertJsonPath('data.name', 'Renamed Tenant');
});

it('soft deletes a tenant', function () {
    $response = $this->actingAs($this->superAdmin)->deleteJson("/api/admin/tenants/{$this->tenantB->uuid}");

    $response->assertNoContent();
    expect(Tenant::withTrashed()->find($this->tenantB->id)->trashed())->toBeTrue();
});

it('forbids a regular user from deleting a tenant', function () {
    $response = $this->actingAs($this->userA)->deleteJson("/api/admin/tenants/{$this->tenantA->uuid}");

    $response->assertForbidden();
});
