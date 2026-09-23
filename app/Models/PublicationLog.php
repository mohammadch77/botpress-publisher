<?php

namespace App\Models;

use App\Enums\LogLevel;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicationLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'publication_id',
        'tenant_id',
        'level',
        'stage',
        'message',
        'context',
    ];

    protected $casts = [
        'level' => LogLevel::class,
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }
}
