<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Project\Queries;

use App\Infrastructure\Persistence\Project\Mappers\ProjectListItemMapper;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Domain\Exception\ProjectNotFound;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlProjectLookupQuery implements ProjectLookupQuery
{
    public function get(OrganizationId $organizationId, ProjectId $projectId): ProjectListItem
    {
        $project = ProjectRecord::query()
            ->select([
                'id',
                'name',
                'slug',
                'description',
                'status',
                'version',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId->value())
            ->where('id', $projectId->value())
            ->first();

        if ($project === null) {
            throw ProjectNotFound::forId($projectId, $organizationId);
        }

        return ProjectListItemMapper::fromRecord($project);
    }
}
