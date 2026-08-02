<?php

declare(strict_types=1);

namespace Sentinel\Organization\Domain\Event;

use DateTimeImmutable;
use Sentinel\Organization\Domain\ValueObject\OrganizationStatus;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class OrganizationReactivated implements AggregateDomainEvent
{
    private function __construct(
        private EventId $eventId,
        private OrganizationId $organizationId,
        private DateTimeImmutable $occurredAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
        private IdentityId $actorIdentityId,
        private OrganizationStatus $organizationStatus,
        private ResourceVersion $aggregateVersion,
    ) {}

    public static function fromValues(
        string $eventId,
        string $organizationId,
        DateTimeImmutable $occurredAt,
        string $correlationId,
        ?string $causationId,
        string $actorIdentityId,
        OrganizationStatus $organizationStatus,
        int $aggregateVersion,
    ): self {
        return new self(
            eventId: EventId::fromString($eventId),
            organizationId: OrganizationId::fromString($organizationId),
            occurredAt: $occurredAt,
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
            actorIdentityId: IdentityId::fromString($actorIdentityId),
            organizationStatus: $organizationStatus,
            aggregateVersion: ResourceVersion::fromInt($aggregateVersion),
        );
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

    public function actorIdentityId(): IdentityId
    {
        return $this->actorIdentityId;
    }

    public function organizationStatus(): OrganizationStatus
    {
        return $this->organizationStatus;
    }

    public function eventName(): string
    {
        return 'organization.reactivated';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function aggregateType(): string
    {
        return 'organization';
    }

    public function aggregateId(): string
    {
        return $this->organizationId->value();
    }

    public function aggregateVersion(): ResourceVersion
    {
        return $this->aggregateVersion;
    }

    public function payload(): array
    {
        return [
            'organization_id' => $this->organizationId->value(),
            'actor_identity_id' => $this->actorIdentityId->value(),
            'organization_status' => $this->organizationStatus->value,
        ];
    }
}
