<?php

namespace App\Models;

use App\Enums\ContentAssetRole;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentAsset extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'content_id',
        'asset_id',
        'tenant_id',
        'role',
        'sort_order',
    ];

    protected $casts = [
        'role' => ContentAssetRole::class,
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
