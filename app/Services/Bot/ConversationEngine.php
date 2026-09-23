<?php

namespace App\Services\Bot;

use App\Domain\Bot\Contracts\FlowHandlerInterface;
use App\Domain\Bot\DTOs\IncomingUpdate;
use App\Enums\ConversationStatus;
use App\Models\Bot;
use App\Models\ConversationSession;
use App\Models\PlatformIdentity;
use App\Services\Bot\Flows\MainMenuFlow;

class ConversationEngine
{
    public function __construct(
        private readonly ConversationLogger $logger,
    ) {}

    public function handle(Bot $bot, PlatformIdentity $identity, IncomingUpdate $update): void
    {
        $session = $this->getOrCreateSession($bot, $identity, $update);

        $this->logger->logIncoming($session, $update);

        $handler = $this->resolveFlowHandler($session->current_flow);
        $handler->handle($session, $update);
    }

    public function transitionTo(ConversationSession $session, string $flow, string $step, array $context = []): void
    {
        $session->update([
            'current_flow' => $flow,
            'current_step' => $step,
            'context' => array_merge($session->context ?? [], $context),
        ]);
    }

    public function endSession(ConversationSession $session): void
    {
        $session->update(['status' => ConversationStatus::Completed]);
    }

    public function cancelSession(ConversationSession $session): void
    {
        $session->update(['status' => ConversationStatus::Cancelled]);
    }

    private function getOrCreateSession(Bot $bot, PlatformIdentity $identity, IncomingUpdate $update): ConversationSession
    {
        $chatId = $update->message?->chatId ?? $update->callback?->chatId;

        return ConversationSession::withoutGlobalScopes()
            ->where('bot_id', $bot->id)
            ->where('chat_id', $chatId)
            ->where('status', ConversationStatus::Active)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first() ?? $this->createSession($bot, $identity, $chatId, $update);
    }

    private function createSession(Bot $bot, PlatformIdentity $identity, string $chatId, IncomingUpdate $update): ConversationSession
    {
        return ConversationSession::withoutGlobalScopes()->create([
            'bot_id' => $bot->id,
            'platform_identity_id' => $identity->id,
            'user_id' => $identity->user_id,
            'tenant_id' => $bot->tenant_id,
            'chat_id' => $chatId,
            'chat_type' => $update->message?->chatType ?? 'private',
            'current_flow' => 'main_menu',
            'current_step' => null,
            'status' => ConversationStatus::Active,
        ]);
    }

    private function resolveFlowHandler(?string $flow): FlowHandlerInterface
    {
        return match ($flow) {
            'main_menu' => app(MainMenuFlow::class),
            default => app(MainMenuFlow::class),
        };
    }
}
