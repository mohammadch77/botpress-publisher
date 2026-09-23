<?php

namespace App\Domain\Bot\DTOs;

readonly class IncomingCallback
{
    public function __construct(
        public string $callbackId,
        public string $chatId,
        public string $fromId,
        public int $messageId,
        public string $data,
    ) {}
}
