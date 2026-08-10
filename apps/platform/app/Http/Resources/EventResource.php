<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Sentinel\Event\Domain\Entity\Event;

final class EventResource extends JsonResource
{
    /** @var Event */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id()->value(),
            'type' => 'event',
            'attributes' => [
                'project_id' => $this->resource->projectId()->value(),
                'application_id' => $this->resource->applicationId()->value(),
                'environment_id' => $this->resource->environmentId()->value(),
                'event_source_id' => $this->resource->eventSourceId()->value(),
                'external_event_id' => $this->resource->externalEventId(),
                'event_type' => $this->resource->eventType()->value,
                'category' => $this->resource->category()->value,
                'severity' => $this->resource->severity()?->value,
                'schema_version' => $this->resource->schemaVersion(),
                'occurred_at' => $this->resource->occurredAt()->format(DATE_ATOM),
                'received_at' => $this->resource->receivedAt()->format(DATE_ATOM),
            ],
        ];
    }
}
