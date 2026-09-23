<?php

namespace App\Models;

use App\Enums\Platform;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformIdentity extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'platform',
        'external_user_id',
        'external_username',
        'display_name',
        'language_code',
        'is_bot',
        'is_verified',
        'verification_code',
        'verification_token',
        'verified_at',
        'metadata',
        'last_interaction_at',
    ];

    protected $hidden = [
        'verification_token',
    ];

    protected $casts = [
        'platform' => Platform::class,
        'is_bot' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
        'last_interaction_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversationSessions(): HasMany
    {
        return $this->hasMany(ConversationSession::class);
    }
}
