<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            // Artisan commands (migrations, seeders, queue workers, etc.) never have a
            // tenant context — degrade gracefully rather than error. Pest/PHPUnit also
            // boot the app "in console", so we explicitly keep scoping active whenever
            // we're running the test suite (HTTP feature tests still need isolation).
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            if (! app()->bound('current.tenant.id')) {
                return;
            }

            $tenantId = app('current.tenant.id');

            if ($tenantId) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (! $model->tenant_id && app()->bound('current.tenant.id')) {
                $model->tenant_id = app('current.tenant.id');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
