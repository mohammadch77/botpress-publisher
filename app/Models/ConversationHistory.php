<?php

namespace App\Models;

use App\Enums\MessageDirection;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationHistory extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'tenant_id',
        'direction',
        'message_type',
        'external_message_id',
        'content',
        'metadata',
    ];

    protected $casts = [
        'direction' => MessageDirection::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ConversationSession::class, 'session_id');
    }
}
