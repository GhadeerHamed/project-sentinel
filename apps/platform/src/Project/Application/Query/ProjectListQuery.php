<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Query;

use Sentinel\Project\Application\ReadModel\ProjectListResult;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface ProjectListQuery
{
    public function list(OrganizationId $organizationId, ListProjectsQuery $query): ProjectListResult;
}
