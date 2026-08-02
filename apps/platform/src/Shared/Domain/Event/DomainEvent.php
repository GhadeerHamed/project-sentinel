<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Event;

use DateTimeImmutable;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface DomainEvent
{
    public function eventId(): EventId;

    public function organizationId(): OrganizationId;

    public function occurredAt(): DateTimeImmutable;

    public function correlationId(): CorrelationId;

    public function causationId(): ?CausationId;

    public function eventName(): string;

    public function eventVersion(): int;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
