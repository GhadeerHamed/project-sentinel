<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Sentinel\Application\Application\Result\CreateApplicationResult;

final class ApplicationResource extends JsonResource
{
    /** @var CreateApplicationResult */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->applicationId()->value(),
            'type' => 'application',
            'attributes' => [
                'project_id' => $this->resource->projectId()->value(),
                'name' => $this->resource->name(),
                'slug' => $this->resource->slug(),
                'description' => $this->resource->description(),
                'criticality' => $this->resource->criticality()->value,
                'status' => $this->resource->status()->value,
                'version' => $this->resource->version()->value(),
                'created_at' => $this->resource->createdAt()->format(DATE_ATOM),
                'updated_at' => $this->resource->updatedAt()->format(DATE_ATOM),
            ],
        ];
    }
}
