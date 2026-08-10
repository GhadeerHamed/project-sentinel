<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Event;

use DateTimeImmutable;
use Sentinel\Event\Domain\Entity\Event;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Event\EventId as DomainEventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class EventAccepted implements AggregateDomainEvent
{
    private function __construct(
        private Event $event,
        private DomainEventId $eventId,
        private CorrelationId $correlationId,
    ) {}

    public static function fromEvent(Event $event): self
    {
        $correlationId = $event->correlationId();

        if ($correlationId === null) {
            throw new \LogicException('Accepted Events require a correlation identifier.');
        }

        return new self(
            event: $event,
            eventId: DomainEventId::fromString($event->id()->value()),
            correlationId: $correlationId,
        );
    }

    public function eventId(): DomainEventId
    {
        return $this->eventId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->event->organizationId();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->event->receivedAt();
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->event->causationId();
    }

    public function eventName(): string
    {
        return 'event.accepted';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function aggregateType(): string
    {
        return 'event';
    }

    public function aggregateId(): string
    {
        return $this->event->id()->value();
    }

    public function aggregateVersion(): ResourceVersion
    {
        return ResourceVersion::fromInt(1);
    }

    /** @return array<string, scalar|null> */
    public function payload(): array
    {
        return [
            'event_id' => $this->event->id()->value(),
            'project_id' => $this->event->projectId()->value(),
            'application_id' => $this->event->applicationId()->value(),
            'environment_id' => $this->event->environmentId()->value(),
            'event_source_id' => $this->event->eventSourceId()->value(),
            'event_type' => $this->event->eventType()->value,
            'category' => $this->event->category()->value,
            'severity' => $this->event->severity()?->value,
            'schema_version' => $this->event->schemaVersion(),
            'occurred_at' => $this->event->occurredAt()->format(DATE_ATOM),
            'received_at' => $this->event->receivedAt()->format(DATE_ATOM),
            'correlation_id' => $this->correlationId->value(),
            'causation_id' => $this->event->causationId()?->value(),
            'event_version' => 1,
        ];
    }
}
