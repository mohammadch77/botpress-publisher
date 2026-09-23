<?php

namespace App\Domain\Publication\DTOs;

class PublishResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalId = null,
        public readonly ?string $externalUrl = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $errorCode = null,
    ) {}

    public static function success(?string $externalId = null, ?string $externalUrl = null): self
    {
        return new self(success: true, externalId: $externalId, externalUrl: $externalUrl);
    }

    public static function failure(string $errorMessage, ?string $errorCode = null): self
    {
        return new self(success: false, errorMessage: $errorMessage, errorCode: $errorCode);
    }
}
