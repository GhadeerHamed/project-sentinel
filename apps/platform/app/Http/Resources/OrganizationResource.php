<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Sentinel\Organization\Application\ReadModel\AccessibleOrganization;

final class OrganizationResource extends JsonResource
{
    /** @var AccessibleOrganization */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return ['id' => $this->resource->id(), 'type' => 'organization', 'attributes' => ['name' => $this->resource->name(), 'slug' => $this->resource->slug(), 'status' => $this->resource->status(), 'timezone' => $this->resource->timezone(), 'version' => $this->resource->version(), 'created_at' => $this->resource->createdAt()->format(DATE_ATOM), 'updated_at' => $this->resource->updatedAt()->format(DATE_ATOM)]];
    }
}
