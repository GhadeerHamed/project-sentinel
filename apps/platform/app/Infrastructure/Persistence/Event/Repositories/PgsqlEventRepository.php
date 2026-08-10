<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Repositories;

use App\Infrastructure\Persistence\Event\Mappers\EventMapper;
use App\Infrastructure\Persistence\Event\Records\EventRecord;
use JsonException;
use Sentinel\Event\Domain\Entity\Event;
use Sentinel\Event\Domain\Exception\EventNotFound;
use Sentinel\Event\Domain\Repository\EventRepository;
use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlEventRepository implements EventRepository
{
    public function getById(EventId $eventId, OrganizationId $organizationId): Event
    {
        $record = EventRecord::query()
            ->where('id', $eventId->value())
            ->where('organization_id', $organizationId->value())
            ->first();

        if ($record === null) {
            throw EventNotFound::forId($eventId, $organizationId);
        }

        return EventMapper::toDomain($record);
    }

    public function findByExternalEventId(
        OrganizationId $organizationId,
        EventSourceId $eventSourceId,
        string $externalEventId,
    ): ?Event {
        $record = EventRecord::query()
            ->where('organization_id', $organizationId->value())
            ->where('event_source_id', $eventSourceId->value())
            ->where('external_event_id', $externalEventId)
            ->first();

        return $record === null ? null : EventMapper::toDomain($record);
    }

    public function save(Event $event): void
    {
        EventRecord::query()->create(EventMapper::toPersistence($event));
    }

    public function saveIfExternalEventIdAbsent(Event $event): bool
    {
        $data = EventMapper::toPersistence($event);

        try {
            $data['payload'] = json_encode($data['payload'], JSON_THROW_ON_ERROR);
            $data['attributes'] = json_encode($data['attributes'], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \LogicException('The Event JSON fields could not be persisted.', previous: $exception);
        }

        return EventRecord::query()->insertOrIgnore($data) === 1;
    }
}
