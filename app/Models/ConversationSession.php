<?php

namespace App\Models;

use App\Enums\ChatType;
use App\Enums\ConversationStatus;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversationSession extends Model
{
    use BelongsToTenant, HasUuid;

    protected $fillable = [
        'bot_id',
        'platform_identity_id',
        'user_id',
        'tenant_id',
        'chat_id',
        'chat_type',
        'current_flow',
        'current_step',
        'context',
        'message_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'chat_type' => ChatType::class,
        'context' => 'array',
        'status' => ConversationStatus::class,
        'expires_at' => 'datetime',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function platformIdentity(): BelongsTo
    {
        return $this->belongsTo(PlatformIdentity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ConversationHistory::class, 'session_id');
    }
}
