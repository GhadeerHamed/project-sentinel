<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Repository;

use Sentinel\Event\Domain\Entity\EventSource;
use Sentinel\Event\Domain\Exception\EventSourceNotFound;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface EventSourceRepository
{
    /** @throws EventSourceNotFound */
    public function getById(EventSourceId $eventSourceId, OrganizationId $organizationId): EventSource;

    public function save(EventSource $eventSource): void;
}
