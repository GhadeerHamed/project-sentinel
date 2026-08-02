<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Audit;

use DateTimeImmutable;
use JsonSerializable;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class AuditRecord implements JsonSerializable
{
    private function __construct(
        private AuditRecordId $id,
        private ?OrganizationId $organizationId,
        private AuditActor $actor,
        private string $action,
        private AuditResource $resource,
        private DateTimeImmutable $occurredAt,
        private DateTimeImmutable $recordedAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
    ) {}

    public static function fromValues(
        string $id,
        ?string $organizationId,
        string $actorType,
        ?string $actorId,
        string $action,
        string $resourceType,
        ?string $resourceId,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $recordedAt,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        $normalizedAction = trim($action);

        if ($normalizedAction === '') {
            throw InvalidAuditRecord::forField('action');
        }

        return new self(
            id: AuditRecordId::fromString($id),
            organizationId: $organizationId === null ? null : OrganizationId::fromString($organizationId),
            actor: AuditActor::fromValues($actorType, $actorId),
            action: $normalizedAction,
            resource: AuditResource::fromValues($resourceType, $resourceId),
            occurredAt: $occurredAt,
            recordedAt: $recordedAt,
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
        );
    }

    public function id(): AuditRecordId
    {
        return $this->id;
    }

    public function organizationId(): ?OrganizationId
    {
        return $this->organizationId;
    }

    public function actor(): AuditActor
    {
        return $this->actor;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function resource(): AuditResource
    {
        return $this->resource;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->causationId;
    }

    /**
     * @return array{
     *     id: string,
     *     organization_id: ?string,
     *     actor_type: string,
     *     actor_id: ?string,
     *     action: string,
     *     resource_type: string,
     *     resource_id: ?string,
     *     occurred_at: string,
     *     recorded_at: string,
     *     correlation_id: string,
     *     causation_id: ?string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id->value(),
            'organization_id' => $this->organizationId?->value(),
            'actor_type' => $this->actor->type(),
            'actor_id' => $this->actor->id()?->value(),
            'action' => $this->action,
            'resource_type' => $this->resource->type(),
            'resource_id' => $this->resource->id()?->value(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
            'recorded_at' => $this->recordedAt->format(DATE_ATOM),
            'correlation_id' => $this->correlationId->value(),
            'causation_id' => $this->causationId?->value(),
        ];
    }
}
