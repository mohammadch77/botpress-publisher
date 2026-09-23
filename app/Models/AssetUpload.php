<?php

namespace App\Models;

use App\Enums\AssetUploadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetUpload extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'destination_id',
        'tenant_id',
        'external_id',
        'external_url',
        'status',
        'uploaded_at',
        'last_error',
    ];

    protected $casts = [
        'status' => AssetUploadStatus::class,
        'uploaded_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
