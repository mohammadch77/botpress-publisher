<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Bot
 */
class BotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'platform' => $this->platform,
            'name' => $this->name,
            'username' => $this->username,
            'token_preview' => $this->tokenPreview(),
            'webhook_url' => $this->webhook_url,
            'status' => $this->status,
            'last_error' => $this->last_error,
            'last_error_at' => $this->last_error_at,
            'last_webhook_at' => $this->last_webhook_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function tokenPreview(): ?string
    {
        if (! $this->metadata || empty($this->metadata['token_last_four'])) {
            return null;
        }

        return '****'.$this->metadata['token_last_four'];
    }
}
