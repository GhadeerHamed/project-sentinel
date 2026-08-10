<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Sentinel\Application\Application\ReadModel\ApplicationListItem;
use Sentinel\Application\Application\Result\CreateApplicationResult;

final class ApplicationResource extends JsonResource
{
    /** @var CreateApplicationResult|ApplicationListItem */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id(),
            'type' => 'application',
            'attributes' => [
                'project_id' => $this->projectId(),
                'name' => $this->resource->name(),
                'slug' => $this->resource->slug(),
                'description' => $this->resource->description(),
                'criticality' => $this->criticality(),
                'status' => $this->status(),
                'version' => $this->version(),
                'created_at' => $this->resource->createdAt()->format(DATE_ATOM),
                'updated_at' => $this->resource->updatedAt()->format(DATE_ATOM),
            ],
        ];
    }

    private function id(): string
    {
        return $this->resource instanceof ApplicationListItem
            ? $this->resource->id()
            : $this->resource->applicationId()->value();
    }

    private function projectId(): string
    {
        return $this->resource instanceof ApplicationListItem
            ? $this->resource->projectId()
            : $this->resource->projectId()->value();
    }

    private function criticality(): string
    {
        return $this->resource instanceof ApplicationListItem
            ? $this->resource->criticality()
            : $this->resource->criticality()->value;
    }

    private function status(): string
    {
        return $this->resource instanceof ApplicationListItem
            ? $this->resource->status()
            : $this->resource->status()->value;
    }

    private function version(): int
    {
        return $this->resource instanceof ApplicationListItem
            ? $this->resource->version()
            : $this->resource->version()->value();
    }
}
