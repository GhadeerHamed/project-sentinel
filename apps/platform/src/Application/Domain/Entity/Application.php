<?php

declare(strict_types=1);

namespace Sentinel\Application\Domain\Entity;

use DateTimeImmutable;
use Sentinel\Application\Domain\Event\ApplicationArchived;
use Sentinel\Application\Domain\Event\ApplicationCreated;
use Sentinel\Application\Domain\Exception\InvalidApplication;
use Sentinel\Application\Domain\Exception\InvalidApplicationTransition;
use Sentinel\Application\Domain\ValueObject\ApplicationCriticality;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Application\Domain\ValueObject\ApplicationStatus;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\DomainEvent;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class Application
{
    private function __construct(
        private ApplicationId $id,
        private OrganizationId $organizationId,
        private ProjectId $projectId,
        private string $name,
        private string $slug,
        private ?string $description,
        private ApplicationCriticality $criticality,
        private ApplicationStatus $status,
        private IdentityId $createdByIdentityId,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $archivedAt,
        private ResourceVersion $version,
        /** @var list<DomainEvent> */
        private array $recordedEvents = [],
    ) {}

    public static function create(
        string $id,
        string $organizationId,
        string $projectId,
        string $name,
        string $slug,
        ?string $description,
        string $criticality,
        string $createdByIdentityId,
        DateTimeImmutable $at,
        string $eventId,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        $application = self::fromValues(
            id: $id,
            organizationId: $organizationId,
            projectId: $projectId,
            name: $name,
            slug: $slug,
            description: $description,
            criticality: $criticality,
            status: ApplicationStatus::ACTIVE->value,
            createdByIdentityId: $createdByIdentityId,
            createdAt: $at,
            updatedAt: $at,
            archivedAt: null,
            version: 1,
        );

        return $application->withEvent(ApplicationCreated::fromValues(
            eventId: $eventId,
            organizationId: $organizationId,
            projectId: $projectId,
            applicationId: $id,
            occurredAt: $at,
            correlationId: $correlationId,
            causationId: $causationId,
            actorIdentityId: $createdByIdentityId,
            applicationName: $application->name,
            applicationSlug: $application->slug,
            applicationDescription: $application->description,
            applicationCriticality: $application->criticality,
            applicationStatus: $application->status,
            aggregateVersion: $application->version->value(),
        ));
    }

    public static function fromValues(
        string $id,
        string $organizationId,
        string $projectId,
        string $name,
        string $slug,
        ?string $description,
        string $criticality,
        string $status,
        string $createdByIdentityId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $archivedAt,
        int $version,
    ): self {
        $normalizedName = trim($name);
        $normalizedSlug = strtolower(trim($slug));
        $normalizedDescription = $description === null ? null : trim($description);

        if ($normalizedName === '' || mb_strlen($normalizedName) > 255) {
            throw InvalidApplication::forField('name');
        }

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $normalizedSlug) !== 1 || mb_strlen($normalizedSlug) > 255) {
            throw InvalidApplication::forField('slug');
        }

        if ($normalizedDescription === '') {
            $normalizedDescription = null;
        }

        return new self(
            id: ApplicationId::fromString($id),
            organizationId: OrganizationId::fromString($organizationId),
            projectId: ProjectId::fromString($projectId),
            name: $normalizedName,
            slug: $normalizedSlug,
            description: $normalizedDescription,
            criticality: ApplicationCriticality::fromString($criticality),
            status: ApplicationStatus::fromString($status),
            createdByIdentityId: IdentityId::fromString($createdByIdentityId),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            archivedAt: $archivedAt,
            version: ResourceVersion::fromInt($version),
        );
    }

    public function id(): ApplicationId
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

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function criticality(): ApplicationCriticality
    {
        return $this->criticality;
    }

    public function status(): ApplicationStatus
    {
        return $this->status;
    }

    public function createdByIdentityId(): IdentityId
    {
        return $this->createdByIdentityId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function archivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function version(): ResourceVersion
    {
        return $this->version;
    }

    public function archive(DateTimeImmutable $at, string $eventId, string $correlationId, ?string $causationId = null): self
    {
        if ($this->status !== ApplicationStatus::ACTIVE) {
            throw InvalidApplicationTransition::fromTo($this->status, ApplicationStatus::ARCHIVED);
        }

        $archived = new self($this->id, $this->organizationId, $this->projectId, $this->name, $this->slug, $this->description, $this->criticality, ApplicationStatus::ARCHIVED, $this->createdByIdentityId, $this->createdAt, $at, $at, $this->version->next(), $this->recordedEvents);

        return $archived->withEvent(ApplicationArchived::fromValues($eventId, $archived->organizationId->value(), $archived->id->value(), $at, $correlationId, $causationId, $archived->version->value()));
    }

    /** @return list<DomainEvent> */
    public function pullDomainEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function withEvent(DomainEvent $event): self
    {
        $copy = clone $this;
        $copy->recordedEvents[] = $event;

        return $copy;
    }
}
