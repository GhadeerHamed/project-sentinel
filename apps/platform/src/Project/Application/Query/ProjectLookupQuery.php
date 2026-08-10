<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Query;

use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Domain\Exception\ProjectNotFound;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface ProjectLookupQuery
{
    /** @throws ProjectNotFound */
    public function get(OrganizationId $organizationId, ProjectId $projectId): ProjectListItem;
}
