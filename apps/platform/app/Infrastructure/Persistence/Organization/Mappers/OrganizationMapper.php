<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Organization\Mappers;

use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use Sentinel\Organization\Domain\Entity\Organization;

final class OrganizationMapper
{
    public static function toDomain(OrganizationRecord $record): Organization
    {
        return Organization::fromValues(
            id: $record->id,
            name: $record->name,
            slug: $record->slug,
            status: $record->status,
            createdByIdentityId: $record->created_by_identity_id,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
            version: $record->version,
            timezone: $record->timezone,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(Organization $organization): array
    {
        return [
            'id' => $organization->id()->value(),
            'name' => $organization->name(),
            'slug' => $organization->slug(),
            'timezone' => $organization->timezone(),
            'status' => $organization->status()->value,
            'created_by_identity_id' => $organization->createdByIdentityId()->value(),
            'created_at' => $organization->createdAt(),
            'updated_at' => $organization->updatedAt(),
            'version' => $organization->version()->value(),
        ];
    }
}
