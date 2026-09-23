<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaleDestination extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'destination_id',
        'tenant_id',
        'bot_id',
        'external_chat_id',
        'title',
        'username',
        'invite_link',
        'member_count',
        'bot_is_admin',
        'permissions',
    ];

    protected $casts = [
        'bot_is_admin' => 'boolean',
        'permissions' => 'array',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
