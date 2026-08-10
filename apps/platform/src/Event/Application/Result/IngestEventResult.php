<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Result;

use Sentinel\Event\Domain\Entity\Event;

final readonly class IngestEventResult
{
    public function __construct(
        private Event $event,
    ) {}

    public function event(): Event
    {
        return $this->event;
    }
}
