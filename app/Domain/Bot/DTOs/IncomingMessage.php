<?php

namespace App\Domain\Bot\DTOs;

readonly class IncomingMessage
{
    public function __construct(
        public string $chatId,
        public string $chatType,
        public string $fromId,
        public ?string $fromUsername,
        public ?string $fromFirstName,
        public string $messageType,
        public ?string $text,
        public ?string $caption,
        public ?array $mediaFileIds,
        public int $messageId,
        public int $date,
    ) {}
}
