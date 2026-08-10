<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Sentinel\Project\Application\Result\CreateProjectResult;

final class ProjectResource extends JsonResource
{
    /** @var CreateProjectResult */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->projectId()->value(),
            'type' => 'project',
            'attributes' => [
                'name' => $this->resource->name(),
                'slug' => $this->resource->slug(),
                'description' => $this->resource->description(),
                'status' => $this->resource->status()->value,
                'version' => $this->resource->version()->value(),
                'created_at' => $this->resource->createdAt()->format(DATE_ATOM),
                'updated_at' => $this->resource->updatedAt()->format(DATE_ATOM),
            ],
        ];
    }
}
