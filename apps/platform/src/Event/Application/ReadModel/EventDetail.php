<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\ReadModel;

final readonly class EventDetail
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar>  $eventAttributes
     */
    public function __construct(private string $id, private array $attributes, private array $payload, private array $eventAttributes) {}

    public function id(): string
    {
        return $this->id;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    /** @return array<string, mixed> */
    public function eventAttributes(): array
    {
        return $this->eventAttributes;
    }
}
