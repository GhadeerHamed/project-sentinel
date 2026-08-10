<?php

declare(strict_types=1);

namespace Sentinel\Project\Domain\Event;

use DateTimeImmutable;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Project\Domain\ValueObject\ProjectStatus;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class ProjectCreated implements AggregateDomainEvent
{
    private function __construct(
        private EventId $eventId,
        private OrganizationId $organizationId,
        private ProjectId $projectId,
        private DateTimeImmutable $occurredAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
        private IdentityId $actorIdentityId,
        private string $projectName,
        private string $projectSlug,
        private ?string $projectDescription,
        private ProjectStatus $projectStatus,
        private ResourceVersion $aggregateVersion,
    ) {}

    public static function fromValues(
        string $eventId,
        string $organizationId,
        string $projectId,
        DateTimeImmutable $occurredAt,
        string $correlationId,
        ?string $causationId,
        string $actorIdentityId,
        string $projectName,
        string $projectSlug,
        ?string $projectDescription,
        ProjectStatus $projectStatus,
        int $aggregateVersion,
    ): self {
        return new self(
            eventId: EventId::fromString($eventId),
            organizationId: OrganizationId::fromString($organizationId),
            projectId: ProjectId::fromString($projectId),
            occurredAt: $occurredAt,
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
            actorIdentityId: IdentityId::fromString($actorIdentityId),
            projectName: $projectName,
            projectSlug: $projectSlug,
            projectDescription: $projectDescription,
            projectStatus: $projectStatus,
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
        return 'project.created';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function aggregateType(): string
    {
        return 'project';
    }

    public function aggregateId(): string
    {
        return $this->projectId->value();
    }

    public function aggregateVersion(): ResourceVersion
    {
        return $this->aggregateVersion;
    }

    /** @return array<string, string|null> */
    public function payload(): array
    {
        return [
            'project_id' => $this->projectId->value(),
            'actor_identity_id' => $this->actorIdentityId->value(),
            'project_name' => $this->projectName,
            'project_slug' => $this->projectSlug,
            'project_description' => $this->projectDescription,
            'project_status' => $this->projectStatus->value,
        ];
    }
}
