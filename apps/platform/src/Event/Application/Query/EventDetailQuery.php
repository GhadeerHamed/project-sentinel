<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Query;

use Sentinel\Event\Application\ReadModel\EventDetail;
use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface EventDetailQuery
{
    public function get(OrganizationId $organizationId, EventId $eventId): ?EventDetail;
}
