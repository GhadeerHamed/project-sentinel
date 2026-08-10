<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Sentinel\Environment\Application\Result\CreateEnvironmentResult;

final class EnvironmentResource extends JsonResource
{
    /** @var CreateEnvironmentResult */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->environmentId()->value(),
            'type' => 'environment',
            'attributes' => [
                'application_id' => $this->resource->applicationId()->value(),
                'name' => $this->resource->name(),
                'slug' => $this->resource->slug(),
                'environment_type' => $this->resource->environmentType()->value,
                'status' => $this->resource->status()->value,
                'version' => $this->resource->version()->value(),
                'created_at' => $this->resource->createdAt()->format(DATE_ATOM),
                'updated_at' => $this->resource->updatedAt()->format(DATE_ATOM),
            ],
        ];
    }
}
