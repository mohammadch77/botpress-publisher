<?php

namespace App\Domain\Bot\DTOs;

readonly class BotInfo
{
    public function __construct(
        public int $id,
        public string $username,
        public string $firstName,
        public bool $canJoinGroups = false,
        public bool $canReadAllGroupMessages = false,
    ) {}
}
