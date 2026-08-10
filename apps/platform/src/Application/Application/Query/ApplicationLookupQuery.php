<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Query;

use Sentinel\Application\Application\ReadModel\ApplicationListItem;
use Sentinel\Application\Domain\Exception\ApplicationNotFound;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface ApplicationLookupQuery
{
    /** @throws ApplicationNotFound */
    public function get(
        OrganizationId $organizationId,
        ProjectId $projectId,
        ApplicationId $applicationId,
    ): ApplicationListItem;
}
