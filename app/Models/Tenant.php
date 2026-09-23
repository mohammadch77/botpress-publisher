<?php

namespace App\Models;

use App\Enums\TenantPlan;
use App\Enums\TenantStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $hidden = [
        'id',
    ];

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'status',
        'settings',
        'trial_ends_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'plan' => TenantPlan::class,
        'status' => TenantStatus::class,
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }
}
