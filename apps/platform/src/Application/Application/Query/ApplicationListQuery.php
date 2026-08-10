<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Query;

use Sentinel\Application\Application\ReadModel\ApplicationListResult;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface ApplicationListQuery
{
    public function list(OrganizationId $organizationId, ProjectId $projectId, ListApplicationsQuery $query): ApplicationListResult;
}
