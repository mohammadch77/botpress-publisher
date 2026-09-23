<?php

namespace App\Services\Bot;

use App\Domain\Bot\DTOs\IncomingUpdate;
use App\Models\Bot;
use Illuminate\Support\Facades\Log;

class BotUpdateHandler
{
    public function __construct(
        private readonly ConversationEngine $conversationEngine,
        private readonly PlatformIdentityService $platformIdentityService,
    ) {}

    public function handle(Bot $bot, IncomingUpdate $update): void
    {
        try {
            $identity = $this->platformIdentityService->findOrCreate($bot, $update);

            $bot->update(['last_webhook_at' => now()]);

            $this->conversationEngine->handle($bot, $identity, $update);
        } catch (\Throwable $e) {
            Log::error('BotUpdateHandler failed', [
                'bot_uuid' => $bot->uuid,
                'platform' => $bot->platform,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
