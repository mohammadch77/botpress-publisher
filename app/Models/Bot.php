<?php

namespace App\Models;

use App\Enums\BotStatus;
use App\Enums\Platform;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bot extends Model
{
    use BelongsToTenant, HasUuid, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'platform',
        'name',
        'username',
        'token_encrypted',
        'token_hash',
        'webhook_url',
        'webhook_secret',
        'status',
        'last_error',
        'last_error_at',
        'last_webhook_at',
        'metadata',
    ];

    protected $hidden = [
        'token_encrypted',
        'webhook_secret',
    ];

    protected $casts = [
        'uuid' => 'string',
        'platform' => Platform::class,
        'status' => BotStatus::class,
        'last_error_at' => 'datetime',
        'last_webhook_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function conversationSessions(): HasMany
    {
        return $this->hasMany(ConversationSession::class);
    }

    public function telegramDestinations(): HasMany
    {
        return $this->hasMany(TelegramDestination::class);
    }

    public function baleDestinations(): HasMany
    {
        return $this->hasMany(BaleDestination::class);
    }
}
