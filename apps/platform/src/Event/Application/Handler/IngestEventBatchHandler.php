<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Handler;

use DateTimeImmutable;
use Sentinel\Event\Application\Authentication\EventSourceIdentity;
use Sentinel\Event\Application\Command\IngestEventBatchCommand;
use Sentinel\Event\Application\Result\IngestEventBatchResult;
use Sentinel\Event\Domain\Entity\Event;
use Sentinel\Event\Domain\Event\EventAccepted;
use Sentinel\Event\Domain\Exception\EventExternalIdConflict;
use Sentinel\Event\Domain\Repository\EventRepository;
use Sentinel\Event\Domain\ValueObject\EventEnvelope;
use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Shared\Application\Idempotency\IdempotentOperation;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRequest;
use Sentinel\Shared\Domain\Idempotency\IdempotencyResponse;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;

final readonly class IngestEventBatchHandler
{
    public function __construct(private EventRepository $events, private IngestEventHandler $singleIngestion, private IdempotentOperation $idempotentOperation, private UuidV7Generator $ids, private OutboxMessageStore $outbox) {}

    public function handle(IngestEventBatchCommand $command, EventSourceIdentity $source): IngestEventBatchResult
    {
        $request = IdempotencyRequest::fromValues($source->organizationId(), IdentityId::fromString($source->eventSourceId()->value()), 'event.ingest.batch', $command->idempotencyKey(), $command->normalizedHash());
        $execution = $this->idempotentOperation->execute($request, function (DateTimeImmutable $at) use ($command, $source): IdempotencyResponse {
            $this->singleIngestion->assertActiveHierarchy($source);
            $eventIds = [];
            /** @var array<string, string> $resolved */
            $resolved = [];
            foreach ($command->events() as $envelope) {
                $eventIds[] = $this->resolve($envelope, $source, $at, $resolved);
            }

            return IdempotencyResponse::fromResultPayload(202, ['event_ids' => $eventIds]);
        });
        $payload = $execution->response()->resultPayload();
        $eventIds = $payload['event_ids'] ?? null;
        if (! is_array($eventIds)) {
            throw new \LogicException('The batch replay payload is invalid.');
        }
        $events = [];
        foreach ($eventIds as $eventId) {
            if (! is_string($eventId)) {
                throw new \LogicException('The batch replay payload is invalid.');
            }
            $events[] = $this->events->getById(EventId::fromString($eventId), $source->organizationId());
        }

        return new IngestEventBatchResult($events);
    }

    /** @param array<string, string> $resolved */
    private function resolve(EventEnvelope $envelope, EventSourceIdentity $source, DateTimeImmutable $at, array &$resolved): string
    {
        $externalId = $envelope->externalEventId();
        if ($externalId !== null && isset($resolved[$externalId])) {
            return $resolved[$externalId];
        }
        $event = Event::accept($this->ids->generate(), $source->organizationId()->value(), $source->projectId()->value(), $source->applicationId()->value(), $source->environmentId()->value(), $source->eventSourceId()->value(), $envelope, $at, $envelope->correlationId()?->value() ?? $this->ids->generate());
        $existing = $externalId === null ? null : $this->events->findByExternalEventId($source->organizationId(), $source->eventSourceId(), $externalId);
        if ($existing !== null) {
            if (! hash_equals($existing->normalizedEventHash(), $event->normalizedEventHash())) {
                throw EventExternalIdConflict::forExternalEventId($source->organizationId(), $source->eventSourceId(), $externalId);
            }
            $resolved[$externalId] = $existing->id()->value();

            return $existing->id()->value();
        }
        if ($externalId !== null && ! $this->events->saveIfExternalEventIdAbsent($event)) {
            $existing = $this->events->findByExternalEventId($source->organizationId(), $source->eventSourceId(), $externalId);
            if ($existing === null) {
                throw new \LogicException('The concurrent source duplicate Event could not be loaded.');
            }
            if (! hash_equals($existing->normalizedEventHash(), $event->normalizedEventHash())) {
                throw EventExternalIdConflict::forExternalEventId($source->organizationId(), $source->eventSourceId(), $externalId);
            }
            $resolved[$externalId] = $existing->id()->value();

            return $existing->id()->value();
        }
        if ($externalId === null) {
            $this->events->save($event);
        }
        $this->outbox->append(OutboxMessage::fromDomainEvent($this->ids->generate(), EventAccepted::fromEvent($event), $at));
        if ($externalId !== null) {
            $resolved[$externalId] = $event->id()->value();
        }

        return $event->id()->value();
    }
}
