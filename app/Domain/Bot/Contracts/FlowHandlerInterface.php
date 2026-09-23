<?php

namespace App\Domain\Bot\Contracts;

use App\Domain\Bot\DTOs\IncomingUpdate;
use App\Models\ConversationSession;

interface FlowHandlerInterface
{
    public function handle(ConversationSession $session, IncomingUpdate $update): void;
}
