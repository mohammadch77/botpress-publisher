<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use BelongsToTenant, HasApiTokens, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'status',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'uuid' => 'string',
        'status' => UserStatus::class,
        'last_seen_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['tenant_id', 'assigned_by', 'assigned_at']);
    }

    public function platformIdentities(): HasMany
    {
        return $this->hasMany(PlatformIdentity::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'owner_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'owner_id');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class, 'created_by');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->contains(
            fn (Role $role) => $role->permissions->contains('name', $permission)
        );
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
}
