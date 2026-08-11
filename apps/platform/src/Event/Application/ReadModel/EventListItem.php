<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\ReadModel;

use DateTimeImmutable;

final readonly class EventListItem
{
    /** @param array<string, string|null> $attributes */
    public function __construct(private string $id, private array $attributes, private DateTimeImmutable $occurredAt) {}

    public function id(): string
    {
        return $this->id;
    }

    /** @return array<string, string|null> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
