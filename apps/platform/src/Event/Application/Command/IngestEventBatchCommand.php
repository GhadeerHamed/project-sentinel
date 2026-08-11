<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Command;

use JsonException;
use Sentinel\Event\Domain\Exception\InvalidEvent;
use Sentinel\Event\Domain\ValueObject\EventEnvelope;

final readonly class IngestEventBatchCommand
{
    /** @var list<EventEnvelope> */
    private array $events;

    /**
     * @param  list<IngestEventBatchItem>  $items
     */
    private function __construct(array $items, private string $idempotencyKey)
    {
        if (count($items) < 1 || count($items) > 100) {
            throw InvalidEvent::forField('events');
        }

        $events = [];
        /** @var array<string, string> $externalIds */
        $externalIds = [];
        foreach ($items as $item) {
            $event = EventEnvelope::fromValues($item->externalEventId, $item->eventType, $item->schemaVersion, $item->severity, $item->occurredAt, $item->payload, $item->attributes, $item->correlationId, $item->causationId, $item->traceId);
            $externalEventId = $event->externalEventId();
            if ($externalEventId !== null && isset($externalIds[$externalEventId]) && ! hash_equals($externalIds[$externalEventId], $event->normalizedHash())) {
                throw InvalidEvent::forField('external_event_id');
            }
            if ($externalEventId !== null) {
                $externalIds[$externalEventId] = $event->normalizedHash();
            }
            $events[] = $event;
        }
        $this->events = $events;
    }

    /** @param list<IngestEventBatchItem> $items */
    public static function fromItems(array $items, string $idempotencyKey): self
    {
        return new self($items, $idempotencyKey);
    }

    /** @return list<EventEnvelope> */
    public function events(): array
    {
        return $this->events;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function normalizedHash(): string
    {
        try {
            return hash('sha256', json_encode(array_map(static fn (EventEnvelope $event): string => $event->normalizedHash(), $this->events), JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            throw new \LogicException('Batch normalization could not be encoded.');
        }
    }
}
