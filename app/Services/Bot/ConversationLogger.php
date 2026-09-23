<?php

namespace App\Services\Bot;

use App\Domain\Bot\DTOs\BotResponse;
use App\Domain\Bot\DTOs\IncomingUpdate;
use App\Enums\MessageDirection;
use App\Models\ConversationHistory;
use App\Models\ConversationSession;

class ConversationLogger
{
    public function logIncoming(ConversationSession $session, IncomingUpdate $update): void
    {
        ConversationHistory::create([
            'session_id' => $session->id,
            'tenant_id' => $session->tenant_id,
            'direction' => MessageDirection::Incoming,
            'message_type' => $update->message?->messageType ?? 'callback_query',
            'external_message_id' => $update->message?->messageId ?? $update->callback?->messageId,
            'content' => $update->message?->text ?? $update->callback?->data,
        ]);
    }

    public function logOutgoing(ConversationSession $session, BotResponse $response, string $messageType = 'text'): void
    {
        ConversationHistory::create([
            'session_id' => $session->id,
            'tenant_id' => $session->tenant_id,
            'direction' => MessageDirection::Outgoing,
            'message_type' => $messageType,
            'external_message_id' => $response->messageId !== null ? (string) $response->messageId : null,
        ]);
    }
}
