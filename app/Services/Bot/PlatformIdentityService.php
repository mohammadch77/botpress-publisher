<?php

namespace App\Services\Bot;

use App\Domain\Bot\DTOs\IncomingUpdate;
use App\Models\Bot;
use App\Models\PlatformIdentity;

class PlatformIdentityService
{
    public function findOrCreate(Bot $bot, IncomingUpdate $update): PlatformIdentity
    {
        $fromId = $update->message?->fromId ?? $update->callback?->fromId;

        return PlatformIdentity::withoutGlobalScopes()
            ->firstOrCreate(
                [
                    'platform' => $bot->platform,
                    'external_user_id' => $fromId,
                ],
                [
                    'tenant_id' => $bot->tenant_id,
                    'display_name' => $update->message?->fromFirstName,
                    'external_username' => $update->message?->fromUsername,
                    'last_interaction_at' => now(),
                ]
            );
    }

    public function updateLastInteraction(PlatformIdentity $identity): void
    {
        $identity->update(['last_interaction_at' => now()]);
    }
}
