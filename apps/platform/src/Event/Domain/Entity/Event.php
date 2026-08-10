<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Event\Domain\Exception\InvalidEvent;
use Sentinel\Event\Domain\ValueObject\EventCategory;
use Sentinel\Event\Domain\ValueObject\EventEnvelope;
use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Event\Domain\ValueObject\EventSeverity;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Event\Domain\ValueObject\EventType;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class Event
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar>  $attributes
     */
    private function __construct(
        private EventId $id,
        private OrganizationId $organizationId,
        private ProjectId $projectId,
        private ApplicationId $applicationId,
        private EnvironmentId $environmentId,
        private EventSourceId $eventSourceId,
        private ?string $externalEventId,
        private EventType $eventType,
        private EventCategory $category,
        private ?EventSeverity $severity,
        private string $schemaVersion,
        private DateTimeImmutable $occurredAt,
        private DateTimeImmutable $receivedAt,
        private array $payload,
        private array $attributes,
        private ?CorrelationId $correlationId,
        private ?CausationId $causationId,
        private ?string $traceId,
        private string $normalizedEventHash,
        private DateTimeImmutable $createdAt,
    ) {}

    public static function accept(
        string $id,
        string $organizationId,
        string $projectId,
        string $applicationId,
        string $environmentId,
        string $eventSourceId,
        EventEnvelope $envelope,
        DateTimeImmutable $acceptedAt,
        ?string $correlationId,
    ): self {
        return self::fromEnvelope(
            id: $id,
            organizationId: $organizationId,
            projectId: $projectId,
            applicationId: $applicationId,
            environmentId: $environmentId,
            eventSourceId: $eventSourceId,
            envelope: $envelope,
            receivedAt: $acceptedAt,
            createdAt: $acceptedAt,
            correlationId: $correlationId,
            normalizedEventHash: $envelope->normalizedHash(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $attributes
     */
    public static function fromValues(
        string $id,
        string $organizationId,
        string $projectId,
        string $applicationId,
        string $environmentId,
        string $eventSourceId,
        ?string $externalEventId,
        string $eventType,
        ?string $severity,
        string $schemaVersion,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $receivedAt,
        array $payload,
        array $attributes,
        ?string $correlationId,
        ?string $causationId,
        ?string $traceId,
        string $normalizedEventHash,
        DateTimeImmutable $createdAt,
    ): self {
        $envelope = EventEnvelope::fromValues(
            externalEventId: $externalEventId,
            eventType: $eventType,
            schemaVersion: $schemaVersion,
            severity: $severity,
            occurredAt: $occurredAt->format(DATE_ATOM),
            payload: $payload,
            attributes: $attributes,
            correlationId: $correlationId,
            causationId: $causationId,
            traceId: $traceId,
        );

        return self::fromEnvelope(
            id: $id,
            organizationId: $organizationId,
            projectId: $projectId,
            applicationId: $applicationId,
            environmentId: $environmentId,
            eventSourceId: $eventSourceId,
            envelope: $envelope,
            receivedAt: $receivedAt,
            createdAt: $createdAt,
            correlationId: $correlationId,
            normalizedEventHash: $normalizedEventHash,
        );
    }

    private static function fromEnvelope(
        string $id,
        string $organizationId,
        string $projectId,
        string $applicationId,
        string $environmentId,
        string $eventSourceId,
        EventEnvelope $envelope,
        DateTimeImmutable $receivedAt,
        DateTimeImmutable $createdAt,
        ?string $correlationId,
        string $normalizedEventHash,
    ): self {
        return new self(
            id: EventId::fromString($id),
            organizationId: OrganizationId::fromString($organizationId),
            projectId: ProjectId::fromString($projectId),
            applicationId: ApplicationId::fromString($applicationId),
            environmentId: EnvironmentId::fromString($environmentId),
            eventSourceId: EventSourceId::fromString($eventSourceId),
            externalEventId: $envelope->externalEventId(),
            eventType: $envelope->eventType(),
            category: $envelope->eventType()->category(),
            severity: $envelope->severity(),
            schemaVersion: $envelope->schemaVersion(),
            occurredAt: $envelope->occurredAt(),
            receivedAt: $receivedAt->setTimezone(new DateTimeZone('UTC')),
            payload: $envelope->payload(),
            attributes: $envelope->attributes(),
            correlationId: $correlationId === null ? null : CorrelationId::fromString($correlationId),
            causationId: $envelope->causationId(),
            traceId: $envelope->traceId(),
            normalizedEventHash: self::normalizedHash($normalizedEventHash),
            createdAt: $createdAt->setTimezone(new DateTimeZone('UTC')),
        );
    }

    public function id(): EventId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function applicationId(): ApplicationId
    {
        return $this->applicationId;
    }

    public function environmentId(): EnvironmentId
    {
        return $this->environmentId;
    }

    public function eventSourceId(): EventSourceId
    {
        return $this->eventSourceId;
    }

    public function externalEventId(): ?string
    {
        return $this->externalEventId;
    }

    public function eventType(): EventType
    {
        return $this->eventType;
    }

    public function category(): EventCategory
    {
        return $this->category;
    }

    public function severity(): ?EventSeverity
    {
        return $this->severity;
    }

    public function schemaVersion(): string
    {
        return $this->schemaVersion;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function receivedAt(): DateTimeImmutable
    {
        return $this->receivedAt;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    /** @return array<string, scalar> */
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

    public function normalizedEventHash(): string
    {
        return $this->normalizedEventHash;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private static function normalizedHash(string $value): string
    {
        $normalized = strtolower(trim($value));

        if (preg_match('/\A[0-9a-f]{64}\z/', $normalized) !== 1) {
            throw InvalidEvent::forField('normalized_event_hash');
        }

        return $normalized;
    }
}
