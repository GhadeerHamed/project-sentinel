<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Application\Mappers;

use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use Sentinel\Application\Domain\Entity\Application;

final class ApplicationMapper
{
    public static function toDomain(ApplicationRecord $record): Application
    {
        return Application::fromValues(
            id: $record->id,
            organizationId: $record->organization_id,
            projectId: $record->project_id,
            name: $record->name,
            slug: $record->slug,
            description: $record->description,
            criticality: $record->criticality,
            status: $record->status,
            createdByIdentityId: $record->created_by_identity_id,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
            archivedAt: $record->archived_at,
            version: $record->version,
        );
    }

    /** @return array<string, mixed> */
    public static function toPersistence(Application $application): array
    {
        return [
            'id' => $application->id()->value(),
            'organization_id' => $application->organizationId()->value(),
            'project_id' => $application->projectId()->value(),
            'name' => $application->name(),
            'slug' => $application->slug(),
            'description' => $application->description(),
            'criticality' => $application->criticality()->value,
            'status' => $application->status()->value,
            'created_by_identity_id' => $application->createdByIdentityId()->value(),
            'created_at' => $application->createdAt(),
            'updated_at' => $application->updatedAt(),
            'archived_at' => $application->archivedAt(),
            'version' => $application->version()->value(),
        ];
    }
}
