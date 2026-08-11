<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\ReadModel;

final readonly class EventListResult
{
    /** @param list<EventListItem> $events */
    public function __construct(private array $events, private ?string $nextCursor) {}

    /** @return list<EventListItem> */
    public function events(): array
    {
        return $this->events;
    }

    public function nextCursor(): ?string
    {
        return $this->nextCursor;
    }

    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }
}
