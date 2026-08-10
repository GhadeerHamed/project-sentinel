<?php

declare(strict_types=1);

namespace Sentinel\Environment\Domain\Event;

use DateTimeImmutable;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentStatus;
use Sentinel\Environment\Domain\ValueObject\EnvironmentType;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class EnvironmentCreated implements AggregateDomainEvent
{
    private function __construct(
        private EventId $eventId,
        private OrganizationId $organizationId,
        private ApplicationId $applicationId,
        private EnvironmentId $environmentId,
        private DateTimeImmutable $occurredAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
        private IdentityId $actorIdentityId,
        private string $environmentName,
        private string $environmentSlug,
        private EnvironmentType $environmentType,
        private EnvironmentStatus $environmentStatus,
        private ResourceVersion $aggregateVersion,
    ) {}

    public static function fromValues(
        string $eventId,
        string $organizationId,
        string $applicationId,
        string $environmentId,
        DateTimeImmutable $occurredAt,
        string $correlationId,
        ?string $causationId,
        string $actorIdentityId,
        string $environmentName,
        string $environmentSlug,
        EnvironmentType $environmentType,
        EnvironmentStatus $environmentStatus,
        int $aggregateVersion,
    ): self {
        return new self(
            eventId: EventId::fromString($eventId),
            organizationId: OrganizationId::fromString($organizationId),
            applicationId: ApplicationId::fromString($applicationId),
            environmentId: EnvironmentId::fromString($environmentId),
            occurredAt: $occurredAt,
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
            actorIdentityId: IdentityId::fromString($actorIdentityId),
            environmentName: $environmentName,
            environmentSlug: $environmentSlug,
            environmentType: $environmentType,
            environmentStatus: $environmentStatus,
            aggregateVersion: ResourceVersion::fromInt($aggregateVersion),
        );
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->causationId;
    }

    public function eventName(): string
    {
        return 'environment.created';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function aggregateType(): string
    {
        return 'environment';
    }

    public function aggregateId(): string
    {
        return $this->environmentId->value();
    }

    public function aggregateVersion(): ResourceVersion
    {
        return $this->aggregateVersion;
    }

    /** @return array<string, string> */
    public function payload(): array
    {
        return [
            'application_id' => $this->applicationId->value(),
            'environment_id' => $this->environmentId->value(),
            'actor_identity_id' => $this->actorIdentityId->value(),
            'environment_name' => $this->environmentName,
            'environment_slug' => $this->environmentSlug,
            'environment_type' => $this->environmentType->value,
            'environment_status' => $this->environmentStatus->value,
        ];
    }
}
