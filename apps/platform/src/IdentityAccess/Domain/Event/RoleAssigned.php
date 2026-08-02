<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Event;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class RoleAssigned implements AggregateDomainEvent
{
    private function __construct(
        private EventId $eventId,
        private OrganizationId $organizationId,
        private DateTimeImmutable $occurredAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
        private RoleAssignmentId $roleAssignmentId,
        private IdentityId $identityId,
        private RoleId $roleId,
        private IdentityId $assignedByIdentityId,
        private ResourceVersion $aggregateVersion,
    ) {}

    public static function fromValues(
        string $eventId,
        string $organizationId,
        DateTimeImmutable $occurredAt,
        string $correlationId,
        ?string $causationId,
        string $roleAssignmentId,
        string $identityId,
        string $roleId,
        string $assignedByIdentityId,
        int $aggregateVersion,
    ): self {
        return new self(
            eventId: EventId::fromString($eventId),
            organizationId: OrganizationId::fromString($organizationId),
            occurredAt: $occurredAt,
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
            roleAssignmentId: RoleAssignmentId::fromString($roleAssignmentId),
            identityId: IdentityId::fromString($identityId),
            roleId: RoleId::fromString($roleId),
            assignedByIdentityId: IdentityId::fromString($assignedByIdentityId),
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

    public function eventName(): string
    {
        return 'role.assigned';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function aggregateType(): string
    {
        return 'role_assignment';
    }

    public function aggregateId(): string
    {
        return $this->roleAssignmentId->value();
    }

    public function aggregateVersion(): ResourceVersion
    {
        return $this->aggregateVersion;
    }

    public function payload(): array
    {
        return [
            'role_assignment_id' => $this->roleAssignmentId->value(),
            'identity_id' => $this->identityId->value(),
            'role_id' => $this->roleId->value(),
            'assigned_by_identity_id' => $this->assignedByIdentityId->value(),
        ];
    }
}
