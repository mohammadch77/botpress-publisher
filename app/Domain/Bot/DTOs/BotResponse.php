<?php

namespace App\Domain\Bot\DTOs;

readonly class BotResponse
{
    public function __construct(
        public bool $ok,
        public ?int $messageId = null,
        public ?string $chatId = null,
        public ?array $raw = null,
        public ?string $errorDescription = null,
        public ?int $errorCode = null,
    ) {}
}
