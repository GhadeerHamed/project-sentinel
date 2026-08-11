<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Query;

use DateTimeImmutable;

final readonly class ListEventsQuery
{
    /** @param array<string, string|null> $filters */
    public function __construct(private array $filters, private int $limit, private ?DateTimeImmutable $cursorOccurredAt = null, private ?string $cursorId = null) {}

    /** @return array<string, string|null> */
    public function filters(): array
    {
        return $this->filters;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function cursorOccurredAt(): ?DateTimeImmutable
    {
        return $this->cursorOccurredAt;
    }

    public function cursorId(): ?string
    {
        return $this->cursorId;
    }

    public function filterHash(): string
    {
        return hash('sha256', json_encode($this->filters, JSON_THROW_ON_ERROR));
    }
}
