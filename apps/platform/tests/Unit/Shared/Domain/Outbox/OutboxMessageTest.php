<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Outbox;

use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Event\DomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
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
            aggregateType: 'alert',
            aggregateId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073b06',
            aggregateVersion: 3,
            payload: ['alert_id' => '01890f7a-5b7c-7cc0-98c4-dc0c0c073b06'],
            createdAt: new DateTimeImmutable('2026-08-02T10:00:01+00:00'),
        );

        self::assertSame(
            '{"id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073b05","organization_id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073b02","event_id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073b01","event_type":"alert.created","event_version":1,"aggregate_type":"alert","aggregate_id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073b06","aggregate_version":3,"payload":{"alert_id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073b06"},"occurred_at":"2026-08-02T10:00:00+00:00","created_at":"2026-08-02T10:00:01+00:00","correlation_id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073b03","causation_id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073b04","published_at":null,"attempt_count":0,"status":"pending"}',
            json_encode($message, JSON_THROW_ON_ERROR),
        );
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
}

final readonly class ExampleDomainEvent implements DomainEvent
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
}
