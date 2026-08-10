<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Result;

use Sentinel\Event\Domain\Entity\EventSource;

final readonly class RegisterEventSourceResult
{
    public function __construct(
        private EventSource $eventSource,
        private ?IssuedEventSourceCredential $issuedCredential,
    ) {}

    public function eventSource(): EventSource
    {
        return $this->eventSource;
    }

    public function issuedCredential(): ?IssuedEventSourceCredential
    {
        return $this->issuedCredential;
    }
}
