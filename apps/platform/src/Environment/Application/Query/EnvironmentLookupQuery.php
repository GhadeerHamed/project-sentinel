<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Query;

use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\ReadModel\EnvironmentListItem;
use Sentinel\Environment\Domain\Exception\EnvironmentNotFound;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface EnvironmentLookupQuery
{
    /** @throws EnvironmentNotFound */
    public function get(
        OrganizationId $organizationId,
        ApplicationId $applicationId,
        EnvironmentId $environmentId,
    ): EnvironmentListItem;
}
