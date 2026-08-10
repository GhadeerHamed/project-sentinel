<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Result;

use Sentinel\Event\Domain\Entity\EventSourceCredential;

final readonly class IssuedEventSourceCredential
{
    public function __construct(
        private EventSourceCredential $credential,
        private string $bearerCredential,
    ) {}

    public function credential(): EventSourceCredential
    {
        return $this->credential;
    }

    public function bearerCredential(): string
    {
        return $this->bearerCredential;
    }
}
