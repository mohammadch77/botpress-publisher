<?php

namespace App\Models;

use App\Enums\MediaType;
use App\Enums\ParseMode;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramPublicationData extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'publication_id',
        'tenant_id',
        'message_text',
        'caption',
        'parse_mode',
        'media_asset_id',
        'media_type',
        'disable_web_page_preview',
        'disable_notification',
        'reply_to_message_id',
        'inline_keyboard',
        'external_message_id',
    ];

    protected $casts = [
        'parse_mode' => ParseMode::class,
        'media_type' => MediaType::class,
        'disable_web_page_preview' => 'boolean',
        'disable_notification' => 'boolean',
        'inline_keyboard' => 'array',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'media_asset_id');
    }
}
