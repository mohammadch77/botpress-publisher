<?php

namespace App\Domain\Publication\Contracts;

use App\Domain\Publication\DTOs\PublishResult;
use App\Models\Publication;

interface PublisherInterface
{
    public function canPublish(Publication $publication): bool;

    public function publish(Publication $publication): PublishResult;
}
