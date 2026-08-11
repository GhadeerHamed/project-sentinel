<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Query;

use Sentinel\Event\Application\ReadModel\EventListResult;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface EventListQuery
{
    public function list(OrganizationId $organizationId, ListEventsQuery $query): EventListResult;
}
