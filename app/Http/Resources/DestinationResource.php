<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Destination
 */
class DestinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'last_error' => $this->last_error,
            'last_sync_at' => $this->last_sync_at,
            'wordpress_site' => $this->whenLoaded('wordpressSite', fn () => $this->wordpressSite ? [
                'url' => $this->wordpressSite->url,
                'discovery_status' => $this->wordpressSite->discovery_status,
            ] : null),
            'telegram_destination' => $this->whenLoaded('telegramDestination', fn () => $this->telegramDestination ? [
                'bot_id' => $this->telegramDestination->bot_id,
                'external_chat_id' => $this->telegramDestination->external_chat_id,
                'title' => $this->telegramDestination->title,
            ] : null),
            'bale_destination' => $this->whenLoaded('baleDestination', fn () => $this->baleDestination ? [
                'bot_id' => $this->baleDestination->bot_id,
                'external_chat_id' => $this->baleDestination->external_chat_id,
                'title' => $this->baleDestination->title,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
