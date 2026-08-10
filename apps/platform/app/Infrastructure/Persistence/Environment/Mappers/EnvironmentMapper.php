<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Environment\Mappers;

use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use Sentinel\Environment\Domain\Entity\Environment;

final class EnvironmentMapper
{
    public static function toDomain(EnvironmentRecord $record): Environment
    {
        return Environment::fromValues(
            id: $record->id,
            organizationId: $record->organization_id,
            applicationId: $record->application_id,
            name: $record->name,
            slug: $record->slug,
            environmentType: $record->environment_type,
            status: $record->status,
            createdByIdentityId: $record->created_by_identity_id,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
            archivedAt: $record->archived_at,
            version: $record->version,
        );
    }

    /** @return array<string, mixed> */
    public static function toPersistence(Environment $environment): array
    {
        return [
            'id' => $environment->id()->value(),
            'organization_id' => $environment->organizationId()->value(),
            'application_id' => $environment->applicationId()->value(),
            'name' => $environment->name(),
            'slug' => $environment->slug(),
            'environment_type' => $environment->environmentType()->value,
            'status' => $environment->status()->value,
            'created_by_identity_id' => $environment->createdByIdentityId()->value(),
            'created_at' => $environment->createdAt(),
            'updated_at' => $environment->updatedAt(),
            'archived_at' => $environment->archivedAt(),
            'version' => $environment->version()->value(),
        ];
    }
}
