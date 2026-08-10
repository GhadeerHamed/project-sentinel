<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Mappers;

use App\Infrastructure\Persistence\Event\Records\EventSourceRecord;
use Sentinel\Event\Domain\Entity\EventSource;

final class EventSourceMapper
{
    public static function toDomain(EventSourceRecord $record): EventSource
    {
        return EventSource::fromValues(
            id: $record->id,
            organizationId: $record->organization_id,
            projectId: $record->project_id,
            applicationId: $record->application_id,
            environmentId: $record->environment_id,
            sourceType: $record->source_type,
            name: $record->name,
            status: $record->status,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
            version: $record->version,
        );
    }

    /** @return array<string, mixed> */
    public static function toPersistence(EventSource $eventSource): array
    {
        return [
            'id' => $eventSource->id()->value(),
            'organization_id' => $eventSource->organizationId()->value(),
            'project_id' => $eventSource->projectId()->value(),
            'application_id' => $eventSource->applicationId()->value(),
            'environment_id' => $eventSource->environmentId()->value(),
            'source_type' => $eventSource->sourceType()->value,
            'name' => $eventSource->name(),
            'status' => $eventSource->status()->value,
            'created_at' => $eventSource->createdAt(),
            'updated_at' => $eventSource->updatedAt(),
            'version' => $eventSource->version()->value(),
        ];
    }
}
