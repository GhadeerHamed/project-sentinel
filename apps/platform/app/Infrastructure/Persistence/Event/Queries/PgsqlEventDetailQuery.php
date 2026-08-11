<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Queries;

use App\Infrastructure\Persistence\Event\Records\EventRecord;
use Sentinel\Event\Application\Query\EventDetailQuery;
use Sentinel\Event\Application\ReadModel\EventDetail;
use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlEventDetailQuery implements EventDetailQuery
{
    public function get(OrganizationId $organizationId, EventId $eventId): ?EventDetail
    {
        $event = EventRecord::query()->select(['id', 'project_id', 'application_id', 'environment_id', 'event_source_id', 'external_event_id', 'event_type', 'category', 'severity', 'schema_version', 'occurred_at', 'received_at', 'created_at', 'correlation_id', 'causation_id', 'trace_id', 'payload', 'attributes'])->where('organization_id', $organizationId->value())->where('id', $eventId->value())->first();
        if ($event === null) {
            return null;
        }

        return new EventDetail($event->id, ['project_id' => $event->project_id, 'application_id' => $event->application_id, 'environment_id' => $event->environment_id, 'event_source_id' => $event->event_source_id, 'external_event_id' => $event->external_event_id, 'event_type' => $event->event_type, 'category' => $event->category, 'severity' => $event->severity, 'schema_version' => $event->schema_version, 'occurred_at' => $event->occurred_at->format(DATE_ATOM), 'received_at' => $event->received_at->format(DATE_ATOM), 'created_at' => $event->created_at->format(DATE_ATOM), 'correlation_id' => $event->correlation_id, 'causation_id' => $event->causation_id, 'trace_id' => $event->trace_id], $event->payload, $event->attributes);
    }
}
