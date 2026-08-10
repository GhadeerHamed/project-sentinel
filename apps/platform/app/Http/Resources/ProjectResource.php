<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Application\Result\CreateProjectResult;

final class ProjectResource extends JsonResource
{
    /** @var CreateProjectResult|ProjectListItem */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id(),
            'type' => 'project',
            'attributes' => [
                'name' => $this->resource->name(),
                'slug' => $this->resource->slug(),
                'description' => $this->resource->description(),
                'status' => $this->status(),
                'version' => $this->version(),
                'created_at' => $this->resource->createdAt()->format(DATE_ATOM),
                'updated_at' => $this->resource->updatedAt()->format(DATE_ATOM),
            ],
        ];
    }

    private function id(): string
    {
        return $this->resource instanceof ProjectListItem
            ? $this->resource->id()
            : $this->resource->projectId()->value();
    }

    private function status(): string
    {
        return $this->resource instanceof ProjectListItem
            ? $this->resource->status()
            : $this->resource->status()->value;
    }

    private function version(): int
    {
        return $this->resource instanceof ProjectListItem
            ? $this->resource->version()
            : $this->resource->version()->value();
    }
}
