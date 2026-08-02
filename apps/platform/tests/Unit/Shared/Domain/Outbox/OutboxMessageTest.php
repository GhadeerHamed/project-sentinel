<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Outbox;

use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Domain\Event\MembershipInvited;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipStatus;
use Sentinel\Organization\Domain\Event\OrganizationCreated;
use Sentinel\Organization\Domain\ValueObject\OrganizationStatus;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Outbox\InvalidOutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;

final class OutboxMessageTest extends TestCase
{
    /**
     * @throws JsonException
     */
    #[Test]
    public function it_creates_a_serialization_ready_outbox_message_from_a_domain_event(): void
    {
        $event = new ExampleDomainEvent(
            eventId: EventId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c073b01'),
            organizationId: OrganizationId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c073b02'),
            occurredAt: new DateTimeImmutable('2026-08-02T10:00:00+00:00'),
            correlationId: CorrelationId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c073b03'),
            causationId: CausationId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c073b04'),
            eventName: 'alert.created',
            eventVersion: 1,
        );

        $message = OutboxMessage::fromDomainEvent(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073b05',
            event: $event,
            createdAt: new DateTimeImmutable('2026-08-02T10:00:01+00:00'),
        );

        $serialized = $message->jsonSerialize();

        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073b05', $serialized['id']);
        self::assertSame('alert.created', $serialized['event_type']);
        self::assertSame(1, $serialized['event_version']);
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073b06', $serialized['payload']['alert_id']);
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073b07', $serialized['payload']['actor_identity_id']);
    }

    #[Test]
    public function it_rejects_invalid_outbox_metadata(): void
    {
        $this->expectException(InvalidOutboxMessage::class);

        OutboxMessage::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073b05',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073b02',
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073b01',
            eventType: ' ',
            eventVersion: 1,
            aggregateType: 'alert',
            aggregateId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073b06',
            aggregateVersion: 3,
            payload: ['alert_id' => '01890f7a-5b7c-7cc0-98c4-dc0c0c073b06'],
            occurredAt: new DateTimeImmutable('2026-08-02T10:00:00+00:00'),
            createdAt: new DateTimeImmutable('2026-08-02T10:00:01+00:00'),
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073b03',
            causationId: null,
        );
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function it_converts_organization_lifecycle_events_with_full_payload(): void
    {
        $event = OrganizationCreated::fromValues(
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c01',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c02',
            occurredAt: new DateTimeImmutable('2026-08-02T11:00:00+00:00'),
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c03',
            causationId: null,
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c04',
            organizationName: 'Sentinel Org',
            organizationSlug: 'sentinel-org',
            organizationStatus: OrganizationStatus::ACTIVE,
            aggregateVersion: 1,
        );

        $message = OutboxMessage::fromDomainEvent(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c05',
            event: $event,
            createdAt: new DateTimeImmutable('2026-08-02T11:00:01+00:00'),
        );

        $serialized = $message->jsonSerialize();

        self::assertSame('organization.created', $serialized['event_type']);
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073c04', $serialized['payload']['actor_identity_id']);
        self::assertSame('sentinel-org', $serialized['payload']['organization_slug']);
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function it_converts_membership_lifecycle_events_with_full_payload(): void
    {
        $event = MembershipInvited::fromValues(
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c11',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c12',
            occurredAt: new DateTimeImmutable('2026-08-02T12:00:00+00:00'),
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c13',
            causationId: null,
            membershipId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c14',
            memberIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c15',
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c16',
            membershipStatus: MembershipStatus::INVITED,
            aggregateVersion: 1,
        );

        $message = OutboxMessage::fromDomainEvent(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c17',
            event: $event,
            createdAt: new DateTimeImmutable('2026-08-02T12:00:01+00:00'),
        );

        $serialized = $message->jsonSerialize();

        self::assertSame('membership.invited', $serialized['event_type']);
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073c14', $serialized['payload']['membership_id']);
        self::assertSame('invited', $serialized['payload']['membership_status']);
    }
}

final readonly class ExampleDomainEvent implements AggregateDomainEvent
{
    public function __construct(
        private EventId $eventId,
        private OrganizationId $organizationId,
        private DateTimeImmutable $occurredAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
        private string $eventName,
        private int $eventVersion,
    ) {}

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
        return $this->eventName;
    }

    public function eventVersion(): int
    {
        return $this->eventVersion;
    }

    public function payload(): array
    {
        return [
            'actor_identity_id' => IdentityId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c073b07')->value(),
            'alert_id' => '01890f7a-5b7c-7cc0-98c4-dc0c0c073b06',
        ];
    }

    public function aggregateType(): string
    {
        return 'alert';
    }

    public function aggregateId(): string
    {
        return '01890f7a-5b7c-7cc0-98c4-dc0c0c073b06';
    }

    public function aggregateVersion(): ResourceVersion
    {
        return ResourceVersion::fromInt(3);
    }
}
