<?php

namespace App\Models;

use App\Enums\BodyFormat;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use BelongsToTenant, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'owner_id',
        'type',
        'title',
        'body',
        'body_format',
        'status',
        'featured_asset_id',
        'language',
    ];

    protected $casts = [
        'uuid' => 'string',
        'type' => ContentType::class,
        'body_format' => BodyFormat::class,
        'status' => ContentStatus::class,
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function featuredAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'featured_asset_id');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'content_assets')
            ->withPivot(['role', 'sort_order']);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }
}
