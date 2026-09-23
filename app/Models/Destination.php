<?php

namespace App\Models;

use App\Enums\DestinationStatus;
use App\Enums\DestinationType;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Destination extends Model
{
    use BelongsToTenant, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'slug',
        'status',
        'last_error',
        'last_error_at',
        'last_sync_at',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'type' => DestinationType::class,
        'status' => DestinationStatus::class,
        'last_error_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function wordpressSite(): HasOne
    {
        return $this->hasOne(WordPressSite::class);
    }

    public function telegramDestination(): HasOne
    {
        return $this->hasOne(TelegramDestination::class);
    }

    public function baleDestination(): HasOne
    {
        return $this->hasOne(BaleDestination::class);
    }

    public function destinationUsers(): HasMany
    {
        return $this->hasMany(DestinationUser::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function assetUploads(): HasMany
    {
        return $this->hasMany(AssetUpload::class);
    }
}
