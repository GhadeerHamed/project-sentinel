<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Event;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipStatus;
use Sentinel\Shared\Domain\Event\DomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class MembershipSuspended implements DomainEvent
{
    private function __construct(
        private EventId $eventId,
        private OrganizationId $organizationId,
        private DateTimeImmutable $occurredAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
        private MembershipId $membershipId,
        private IdentityId $memberIdentityId,
        private IdentityId $actorIdentityId,
        private MembershipStatus $membershipStatus,
    ) {}

    public static function fromValues(
        string $eventId,
        string $organizationId,
        DateTimeImmutable $occurredAt,
        string $correlationId,
        ?string $causationId,
        string $membershipId,
        string $memberIdentityId,
        string $actorIdentityId,
        MembershipStatus $membershipStatus,
    ): self {
        return new self(
            eventId: EventId::fromString($eventId),
            organizationId: OrganizationId::fromString($organizationId),
            occurredAt: $occurredAt,
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
            membershipId: MembershipId::fromString($membershipId),
            memberIdentityId: IdentityId::fromString($memberIdentityId),
            actorIdentityId: IdentityId::fromString($actorIdentityId),
            membershipStatus: $membershipStatus,
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

    public function membershipId(): MembershipId
    {
        return $this->membershipId;
    }

    public function memberIdentityId(): IdentityId
    {
        return $this->memberIdentityId;
    }

    public function actorIdentityId(): IdentityId
    {
        return $this->actorIdentityId;
    }

    public function membershipStatus(): MembershipStatus
    {
        return $this->membershipStatus;
    }

    public function eventName(): string
    {
        return 'membership.suspended';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function payload(): array
    {
        return [
            'membership_id' => $this->membershipId->value(),
            'member_identity_id' => $this->memberIdentityId->value(),
            'actor_identity_id' => $this->actorIdentityId->value(),
            'membership_status' => $this->membershipStatus->value,
        ];
    }
}
