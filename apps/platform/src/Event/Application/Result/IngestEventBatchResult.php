<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Result;

use Sentinel\Event\Domain\Entity\Event;

final readonly class IngestEventBatchResult
{
    /** @param list<Event> $events */
    public function __construct(private array $events) {}

    /** @return list<Event> */
    public function events(): array
    {
        return $this->events;
    }
}
