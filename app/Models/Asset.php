<?php

namespace App\Models;

use App\Enums\AssetType;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use BelongsToTenant, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'owner_id',
        'type',
        'mime_type',
        'original_name',
        'storage_disk',
        'storage_path',
        'file_size',
        'width',
        'height',
        'duration',
        'checksum',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'type' => AssetType::class,
        'metadata' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(AssetUpload::class);
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_assets')
            ->withPivot(['role', 'sort_order']);
    }
}
