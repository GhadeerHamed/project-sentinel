<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Mappers;

use App\Infrastructure\Persistence\Event\Records\EventRecord;
use Sentinel\Event\Domain\Entity\Event;

final class EventMapper
{
    public static function toDomain(EventRecord $record): Event
    {
        return Event::fromValues(
            id: $record->id,
            organizationId: $record->organization_id,
            projectId: $record->project_id,
            applicationId: $record->application_id,
            environmentId: $record->environment_id,
            eventSourceId: $record->event_source_id,
            externalEventId: $record->external_event_id,
            eventType: $record->event_type,
            severity: $record->severity,
            schemaVersion: $record->schema_version,
            occurredAt: $record->occurred_at,
            receivedAt: $record->received_at,
            payload: $record->payload,
            attributes: $record->attributes,
            correlationId: $record->correlation_id,
            causationId: $record->causation_id,
            traceId: $record->trace_id,
            normalizedEventHash: $record->normalized_event_hash,
            createdAt: $record->created_at,
        );
    }

    /** @return array<string, mixed> */
    public static function toPersistence(Event $event): array
    {
        return [
            'id' => $event->id()->value(),
            'organization_id' => $event->organizationId()->value(),
            'project_id' => $event->projectId()->value(),
            'application_id' => $event->applicationId()->value(),
            'environment_id' => $event->environmentId()->value(),
            'event_source_id' => $event->eventSourceId()->value(),
            'external_event_id' => $event->externalEventId(),
            'event_type' => $event->eventType()->value,
            'category' => $event->category()->value,
            'severity' => $event->severity()?->value,
            'schema_version' => $event->schemaVersion(),
            'occurred_at' => $event->occurredAt(),
            'received_at' => $event->receivedAt(),
            'payload' => self::jsonObject($event->payload()),
            'attributes' => self::jsonObject($event->attributes()),
            'correlation_id' => $event->correlationId()?->value(),
            'causation_id' => $event->causationId()?->value(),
            'trace_id' => $event->traceId(),
            'normalized_event_hash' => $event->normalizedEventHash(),
            'created_at' => $event->createdAt(),
        ];
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>|\stdClass
     */
    private static function jsonObject(array $value): array|\stdClass
    {
        return $value === [] ? new \stdClass : $value;
    }
}
