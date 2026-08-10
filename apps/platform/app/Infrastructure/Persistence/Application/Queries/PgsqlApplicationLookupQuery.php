<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Application\Queries;

use App\Infrastructure\Persistence\Application\Mappers\ApplicationListItemMapper;
use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use Sentinel\Application\Application\Query\ApplicationLookupQuery;
use Sentinel\Application\Application\ReadModel\ApplicationListItem;
use Sentinel\Application\Domain\Exception\ApplicationNotFound;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlApplicationLookupQuery implements ApplicationLookupQuery
{
    public function get(
        OrganizationId $organizationId,
        ProjectId $projectId,
        ApplicationId $applicationId,
    ): ApplicationListItem {
        $application = ApplicationRecord::query()
            ->select([
                'id',
                'project_id',
                'name',
                'slug',
                'description',
                'criticality',
                'status',
                'version',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId->value())
            ->where('project_id', $projectId->value())
            ->where('id', $applicationId->value())
            ->first();

        if ($application === null) {
            throw ApplicationNotFound::forId($applicationId, $organizationId, $projectId);
        }

        return ApplicationListItemMapper::fromRecord($application);
    }
}
