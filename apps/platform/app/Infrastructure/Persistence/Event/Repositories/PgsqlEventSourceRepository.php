<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Repositories;

use App\Infrastructure\Persistence\Event\Mappers\EventSourceMapper;
use App\Infrastructure\Persistence\Event\Records\EventSourceRecord;
use Sentinel\Event\Domain\Entity\EventSource;
use Sentinel\Event\Domain\Exception\EventSourceNotFound;
use Sentinel\Event\Domain\Repository\EventSourceRepository;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlEventSourceRepository implements EventSourceRepository
{
    public function getById(EventSourceId $eventSourceId, OrganizationId $organizationId): EventSource
    {
        $record = EventSourceRecord::query()
            ->where('id', $eventSourceId->value())
            ->where('organization_id', $organizationId->value())
            ->first();

        if ($record === null) {
            throw EventSourceNotFound::forId($eventSourceId, $organizationId);
        }

        return EventSourceMapper::toDomain($record);
    }

    public function save(EventSource $eventSource): void
    {
        EventSourceRecord::query()->create(EventSourceMapper::toPersistence($eventSource));
    }
}
