<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use Sentinel\Event\Domain\Exception\InvalidEvent;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;

final readonly class EventEnvelope
{
    public const SCHEMA_VERSION = '1';

    private const MAX_ENCODED_BYTES = 65_536;

    /**
     * @param  array<mixed>  $payload
     * @param  array<mixed>  $attributes
     */
    private function __construct(
        private ?string $externalEventId,
        private EventType $eventType,
        private string $schemaVersion,
        private ?EventSeverity $severity,
        private DateTimeImmutable $occurredAt,
        private array $payload,
        private array $attributes,
        private ?CorrelationId $correlationId,
        private ?CausationId $causationId,
        private ?string $traceId,
        private string $normalizedHash,
    ) {}

    /**
     * @param  array<mixed>  $payload
     * @param  array<mixed>  $attributes
     */
    public static function fromValues(
        ?string $externalEventId,
        string $eventType,
        string $schemaVersion,
        ?string $severity,
        string $occurredAt,
        array $payload,
        array $attributes = [],
        ?string $correlationId = null,
        ?string $causationId = null,
        ?string $traceId = null,
    ): self {
        $normalizedSchemaVersion = trim($schemaVersion);

        if ($normalizedSchemaVersion !== self::SCHEMA_VERSION) {
            throw InvalidEvent::forField('schema_version');
        }

        if ($payload !== [] && array_is_list($payload)) {
            throw InvalidEvent::forField('payload');
        }

        if ($attributes !== [] && array_is_list($attributes)) {
            throw InvalidEvent::forField('attributes');
        }

        foreach ($attributes as $value) {
            if (! is_scalar($value)) {
                throw InvalidEvent::forField('attributes');
            }
        }

        $normalizedExternalEventId = self::nullableString($externalEventId, 'external_event_id', 255);
        $normalizedTraceId = self::nullableString($traceId, 'trace_id', 255);
        $normalizedOccurredAt = self::timestamp($occurredAt);
        $normalizedCorrelationId = $correlationId === null ? null : CorrelationId::fromString($correlationId);
        $normalizedCausationId = $causationId === null ? null : CausationId::fromString($causationId);
        $normalizedEventType = EventType::fromString($eventType);
        $normalizedSeverity = $severity === null ? null : EventSeverity::fromString($severity);
        $normalizedPayload = self::canonicalize($payload);
        $normalizedAttributes = self::canonicalize($attributes);

        try {
            $payloadJson = json_encode($normalizedPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
            $attributesJson = json_encode($normalizedAttributes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
            $normalizedHash = hash('sha256', json_encode([
                'attributes' => $normalizedAttributes,
                'causation_id' => $normalizedCausationId?->value(),
                'correlation_id' => $normalizedCorrelationId?->value(),
                'event_type' => $normalizedEventType->value,
                'external_event_id' => $normalizedExternalEventId,
                'occurred_at' => $normalizedOccurredAt->format(DATE_ATOM),
                'payload' => $normalizedPayload,
                'schema_version' => $normalizedSchemaVersion,
                'severity' => $normalizedSeverity?->value,
                'trace_id' => $normalizedTraceId,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
        } catch (JsonException) {
            throw InvalidEvent::forField('payload');
        }

        if (strlen($payloadJson) + strlen($attributesJson) > self::MAX_ENCODED_BYTES) {
            throw InvalidEvent::forField('payload');
        }

        /** @var array<mixed> $normalizedPayload */
        /** @var array<mixed> $normalizedAttributes */
        return new self(
            externalEventId: $normalizedExternalEventId,
            eventType: $normalizedEventType,
            schemaVersion: $normalizedSchemaVersion,
            severity: $normalizedSeverity,
            occurredAt: $normalizedOccurredAt,
            payload: $normalizedPayload,
            attributes: $normalizedAttributes,
            correlationId: $normalizedCorrelationId,
            causationId: $normalizedCausationId,
            traceId: $normalizedTraceId,
            normalizedHash: $normalizedHash,
        );
    }

    public function externalEventId(): ?string
    {
        return $this->externalEventId;
    }

    public function eventType(): EventType
    {
        return $this->eventType;
    }

    public function schemaVersion(): string
    {
        return $this->schemaVersion;
    }

    public function severity(): ?EventSeverity
    {
        return $this->severity;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /** @return array<mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    /** @return array<mixed> */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function correlationId(): ?CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->causationId;
    }

    public function traceId(): ?string
    {
        return $this->traceId;
    }

    public function normalizedHash(): string
    {
        return $this->normalizedHash;
    }

    private static function nullableString(?string $value, string $field, int $maximumLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '' || mb_strlen($normalized) > $maximumLength) {
            throw InvalidEvent::forField($field);
        }

        return $normalized;
    }

    private static function timestamp(string $value): DateTimeImmutable
    {
        $normalized = trim($value);

        if ($normalized === '' || preg_match('/(?:Z|[+-]\d{2}:\d{2})\z/', $normalized) !== 1) {
            throw InvalidEvent::forField('occurred_at');
        }

        try {
            return (new DateTimeImmutable($normalized))->setTimezone(new DateTimeZone('UTC'));
        } catch (\Exception) {
            throw InvalidEvent::forField('occurred_at');
        }
    }

    private static function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(self::canonicalize(...), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = self::canonicalize($item);
        }

        return $value;
    }
}
