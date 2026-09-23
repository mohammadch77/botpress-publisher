<?php

use App\Models\Tenant;

it('generates a uuid on creation', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->uuid)->not->toBeNull();
    expect(strlen($tenant->uuid))->toBe(36);
});

it('soft deletes tenants', function () {
    $tenant = Tenant::factory()->create();

    $tenant->delete();

    expect(Tenant::query()->find($tenant->id))->toBeNull();
    expect(Tenant::withTrashed()->find($tenant->id))->not->toBeNull();
});
