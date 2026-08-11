<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Command;

final readonly class IngestEventBatchItem
{
    /**
     * @param  array<mixed>  $payload
     * @param  array<mixed>  $attributes
     */
    public function __construct(
        public string $eventType,
        public string $schemaVersion,
        public string $occurredAt,
        public array $payload,
        public ?string $externalEventId = null,
        public ?string $severity = null,
        public array $attributes = [],
        public ?string $correlationId = null,
        public ?string $causationId = null,
        public ?string $traceId = null,
    ) {}
}
