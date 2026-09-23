<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Publication extends Model
{
    use BelongsToTenant, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'content_id',
        'destination_id',
        'created_by',
        'status',
        'depends_on_publication_id',
        'dependency_field',
        'scheduled_at',
        'published_at',
        'max_attempts',
        'attempts',
        'last_attempt_at',
        'next_retry_at',
        'last_error',
        'last_error_code',
        'external_id',
        'external_url',
    ];

    protected $casts = [
        'uuid' => 'string',
        'idempotency_key' => 'string',
        'status' => PublicationStatus::class,
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    protected static function bootPublication(): void
    {
        static::creating(function (Publication $publication) {
            if (empty($publication->idempotency_key)) {
                $publication->idempotency_key = $publication->generateIdempotencyKey();
            }
        });
    }

    public function generateIdempotencyKey(): string
    {
        return (string) Str::uuid();
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Publication::class, 'depends_on_publication_id');
    }

    public function wordpressData(): HasOne
    {
        return $this->hasOne(WordPressPublicationData::class);
    }

    public function telegramData(): HasOne
    {
        return $this->hasOne(TelegramPublicationData::class);
    }

    public function baleData(): HasOne
    {
        return $this->hasOne(BalePublicationData::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PublicationLog::class);
    }
}
