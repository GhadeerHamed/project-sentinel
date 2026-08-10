<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Sentinel\Environment\Application\ReadModel\EnvironmentListItem;
use Sentinel\Environment\Application\Result\CreateEnvironmentResult;

final class EnvironmentResource extends JsonResource
{
    /** @var CreateEnvironmentResult|EnvironmentListItem */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id(),
            'type' => 'environment',
            'attributes' => [
                'application_id' => $this->applicationId(),
                'name' => $this->resource->name(),
                'slug' => $this->resource->slug(),
                'environment_type' => $this->environmentType(),
                'status' => $this->status(),
                'version' => $this->version(),
                'created_at' => $this->resource->createdAt()->format(DATE_ATOM),
                'updated_at' => $this->resource->updatedAt()->format(DATE_ATOM),
            ],
        ];
    }

    private function id(): string
    {
        return $this->resource instanceof EnvironmentListItem
            ? $this->resource->id()
            : $this->resource->environmentId()->value();
    }

    private function applicationId(): string
    {
        return $this->resource instanceof EnvironmentListItem
            ? $this->resource->applicationId()
            : $this->resource->applicationId()->value();
    }

    private function environmentType(): string
    {
        return $this->resource instanceof EnvironmentListItem
            ? $this->resource->environmentType()
            : $this->resource->environmentType()->value;
    }

    private function status(): string
    {
        return $this->resource instanceof EnvironmentListItem
            ? $this->resource->status()
            : $this->resource->status()->value;
    }

    private function version(): int
    {
        return $this->resource instanceof EnvironmentListItem
            ? $this->resource->version()
            : $this->resource->version()->value();
    }
}
