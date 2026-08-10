<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Query;

use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\ReadModel\EnvironmentListResult;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface EnvironmentListQuery
{
    public function list(OrganizationId $organizationId, ApplicationId $applicationId, ListEnvironmentsQuery $query): EnvironmentListResult;
}
