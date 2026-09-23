<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'email' => 'admin@example.test',
        'password' => Hash::make('password'),
    ]);
});

it('logs in with valid credentials', function () {
    $response = $this->postJson('/api/admin/auth/login', [
        'email' => 'admin@example.test',
        'password' => 'password',
    ]);

    $response->assertOk()->assertJsonPath('user.email', 'admin@example.test');
    $this->assertAuthenticatedAs($this->user);
});

it('rejects invalid credentials', function () {
    $response = $this->postJson('/api/admin/auth/login', [
        'email' => 'admin@example.test',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $this->assertGuest();
});

it('returns the authenticated user from me', function () {
    $response = $this->actingAs($this->user)->getJson('/api/admin/auth/me');

    $response->assertOk()->assertJsonPath('user.email', 'admin@example.test');
});

it('logs out the authenticated user', function () {
    $response = $this->actingAs($this->user)->postJson('/api/admin/auth/logout');

    $response->assertOk();
});
