<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Only apply scope when tenant context exists.
        // DO NOT hard-fail here — let authorization handle it.
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
