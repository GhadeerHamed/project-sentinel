<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Outbox;

use DateTimeImmutable;
use JsonSerializable;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class OutboxMessage implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        private OutboxMessageId $id,
        private ?OrganizationId $organizationId,
        private EventId $eventId,
        private string $eventType,
        private int $eventVersion,
        private string $aggregateType,
        private AggregateId $aggregateId,
        private ResourceVersion $aggregateVersion,
        private array $payload,
        private DateTimeImmutable $occurredAt,
        private DateTimeImmutable $createdAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromValues(
        string $id,
        ?string $organizationId,
        string $eventId,
        string $eventType,
        int $eventVersion,
        string $aggregateType,
        string $aggregateId,
        int $aggregateVersion,
        array $payload,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $createdAt,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        if (trim($eventType) === '') {
            throw InvalidOutboxMessage::forField('event_type');
        }

        if ($eventVersion < 1) {
            throw InvalidOutboxMessage::forField('event_version');
        }

        if (trim($aggregateType) === '') {
            throw InvalidOutboxMessage::forField('aggregate_type');
        }

        return new self(
            id: OutboxMessageId::fromString($id),
            organizationId: $organizationId === null ? null : OrganizationId::fromString($organizationId),
            eventId: EventId::fromString($eventId),
            eventType: $eventType,
            eventVersion: $eventVersion,
            aggregateType: $aggregateType,
            aggregateId: AggregateId::fromString($aggregateId),
            aggregateVersion: ResourceVersion::fromInt($aggregateVersion),
            payload: $payload,
            occurredAt: $occurredAt,
            createdAt: $createdAt,
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
        );
    }

    public static function fromDomainEvent(
        string $id,
        AggregateDomainEvent $event,
        DateTimeImmutable $createdAt,
    ): self {
        return self::fromValues(
            id: $id,
            organizationId: $event->organizationId()->value(),
            eventId: $event->eventId()->value(),
            eventType: $event->eventName(),
            eventVersion: $event->eventVersion(),
            aggregateType: $event->aggregateType(),
            aggregateId: $event->aggregateId(),
            aggregateVersion: $event->aggregateVersion()->value(),
            payload: $event->payload(),
            occurredAt: $event->occurredAt(),
            createdAt: $createdAt,
            correlationId: $event->correlationId()->value(),
            causationId: $event->causationId()?->value(),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     organization_id: ?string,
     *     event_id: string,
     *     event_type: string,
     *     event_version: int,
     *     aggregate_type: string,
     *     aggregate_id: string,
     *     aggregate_version: int,
     *     payload: array<string, mixed>,
     *     occurred_at: string,
     *     created_at: string,
     *     correlation_id: string,
     *     causation_id: ?string,
     *     published_at: null,
     *     attempt_count: int,
     *     status: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id->value(),
            'organization_id' => $this->organizationId?->value(),
            'event_id' => $this->eventId->value(),
            'event_type' => $this->eventType,
            'event_version' => $this->eventVersion,
            'aggregate_type' => $this->aggregateType,
            'aggregate_id' => $this->aggregateId->value(),
            'aggregate_version' => $this->aggregateVersion->value(),
            'payload' => $this->payload,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'correlation_id' => $this->correlationId->value(),
            'causation_id' => $this->causationId?->value(),
            'published_at' => null,
            'attempt_count' => 0,
            'status' => 'pending',
        ];
    }
}
