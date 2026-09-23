<?php

namespace App\Services\Tenant;

use App\Models\Tenant;

class TenantContext
{
    private ?int $tenantId = null;

    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->tenantId = $tenant->id;
        app()->instance('current.tenant.id', $tenant->id);
        app()->instance('current.tenant', $tenant);
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function getId(): ?int
    {
        return $this->tenantId;
    }

    public function isSet(): bool
    {
        return $this->tenantId !== null;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->tenantId = null;
        app()->forgetInstance('current.tenant.id');
        app()->forgetInstance('current.tenant');
    }
}
