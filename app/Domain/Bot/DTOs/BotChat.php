<?php

namespace App\Domain\Bot\DTOs;

readonly class BotChat
{
    public function __construct(
        public string $id,
        public string $type,
        public ?string $title = null,
        public ?string $username = null,
        public ?int $memberCount = null,
    ) {}
}
