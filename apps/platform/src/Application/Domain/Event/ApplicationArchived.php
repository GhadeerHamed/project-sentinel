<?php

declare(strict_types=1);

namespace Sentinel\Application\Domain\Event;

use DateTimeImmutable;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class ApplicationArchived implements AggregateDomainEvent
{
    public function __construct(private EventId $eventId, private OrganizationId $organizationId, private ApplicationId $applicationId, private DateTimeImmutable $occurredAt, private CorrelationId $correlationId, private ?CausationId $causationId, private ResourceVersion $version) {}

    public static function fromValues(string $eventId, string $organizationId, string $applicationId, DateTimeImmutable $at, string $correlationId, ?string $causationId, int $version): self
    {
        return new self(EventId::fromString($eventId), OrganizationId::fromString($organizationId), ApplicationId::fromString($applicationId), $at, CorrelationId::fromString($correlationId), $causationId === null ? null : CausationId::fromString($causationId), ResourceVersion::fromInt($version));
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->causationId;
    }

    public function eventName(): string
    {
        return 'application.archived';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function aggregateType(): string
    {
        return 'application';
    }

    public function aggregateId(): string
    {
        return $this->applicationId->value();
    }

    public function aggregateVersion(): ResourceVersion
    {
        return $this->version;
    }

    public function payload(): array
    {
        return ['application_id' => $this->applicationId->value(), 'application_status' => 'archived'];
    }
}
