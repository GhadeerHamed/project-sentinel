<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Project\Mappers;

use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Sentinel\Project\Domain\Entity\Project;

final class ProjectMapper
{
    public static function toDomain(ProjectRecord $record): Project
    {
        return Project::fromValues(
            id: $record->id,
            organizationId: $record->organization_id,
            name: $record->name,
            slug: $record->slug,
            description: $record->description,
            status: $record->status,
            createdByIdentityId: $record->created_by_identity_id,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
            archivedAt: $record->archived_at,
            version: $record->version,
        );
    }

    /** @return array<string, mixed> */
    public static function toPersistence(Project $project): array
    {
        return [
            'id' => $project->id()->value(),
            'organization_id' => $project->organizationId()->value(),
            'name' => $project->name(),
            'slug' => $project->slug(),
            'description' => $project->description(),
            'status' => $project->status()->value,
            'created_by_identity_id' => $project->createdByIdentityId()->value(),
            'created_at' => $project->createdAt(),
            'updated_at' => $project->updatedAt(),
            'archived_at' => $project->archivedAt(),
            'version' => $project->version()->value(),
        ];
    }
}
