<?php

namespace App\Domain\Bot\DTOs;

readonly class IncomingUpdate
{
    public function __construct(
        public string $platform,
        public string $updateType,
        public int $updateId,
        public ?IncomingMessage $message = null,
        public ?IncomingCallback $callback = null,
    ) {}
}
