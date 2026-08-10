<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Repository;

use Sentinel\Event\Domain\Entity\Event;
use Sentinel\Event\Domain\Exception\EventNotFound;
use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface EventRepository
{
    /** @throws EventNotFound */
    public function getById(EventId $eventId, OrganizationId $organizationId): Event;

    public function findByExternalEventId(
        OrganizationId $organizationId,
        EventSourceId $eventSourceId,
        string $externalEventId,
    ): ?Event;

    public function save(Event $event): void;

    /**
     * Atomically saves an Event with an external identifier when no source duplicate exists.
     */
    public function saveIfExternalEventIdAbsent(Event $event): bool;
}
