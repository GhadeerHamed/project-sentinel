<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Command;

final readonly class IngestEventCommand
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $attributes
     */
    private function __construct(
        private string $eventType,
        private string $schemaVersion,
        private string $occurredAt,
        private array $payload,
        private string $idempotencyKey,
        private ?string $externalEventId,
        private ?string $severity,
        private array $attributes,
        private ?string $correlationId,
        private ?string $causationId,
        private ?string $traceId,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $attributes
     */
    public static function fromValues(
        string $eventType,
        string $schemaVersion,
        string $occurredAt,
        array $payload,
        string $idempotencyKey,
        ?string $externalEventId = null,
        ?string $severity = null,
        array $attributes = [],
        ?string $correlationId = null,
        ?string $causationId = null,
        ?string $traceId = null,
    ): self {
        return new self(
            eventType: $eventType,
            schemaVersion: $schemaVersion,
            occurredAt: $occurredAt,
            payload: $payload,
            idempotencyKey: $idempotencyKey,
            externalEventId: $externalEventId,
            severity: $severity,
            attributes: $attributes,
            correlationId: $correlationId,
            causationId: $causationId,
            traceId: $traceId,
        );
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function schemaVersion(): string
    {
        return $this->schemaVersion;
    }

    public function occurredAt(): string
    {
        return $this->occurredAt;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function externalEventId(): ?string
    {
        return $this->externalEventId;
    }

    public function severity(): ?string
    {
        return $this->severity;
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    public function causationId(): ?string
    {
        return $this->causationId;
    }

    public function traceId(): ?string
    {
        return $this->traceId;
    }
}
