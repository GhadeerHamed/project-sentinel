<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Sentinel\Event\Domain\Entity\EventSource;

final class EventSourceResource extends JsonResource
{
    /** @var EventSource */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id()->value(),
            'type' => 'event_source',
            'attributes' => [
                'project_id' => $this->resource->projectId()->value(),
                'application_id' => $this->resource->applicationId()->value(),
                'environment_id' => $this->resource->environmentId()->value(),
                'source_type' => $this->resource->sourceType()->value,
                'name' => $this->resource->name(),
                'status' => $this->resource->status()->value,
                'version' => $this->resource->version()->value(),
                'created_at' => $this->resource->createdAt()->format(DATE_ATOM),
                'updated_at' => $this->resource->updatedAt()->format(DATE_ATOM),
            ],
        ];
    }
}
