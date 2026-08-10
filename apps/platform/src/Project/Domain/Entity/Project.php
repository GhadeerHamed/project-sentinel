<?php

declare(strict_types=1);

namespace Sentinel\Project\Domain\Entity;

use DateTimeImmutable;
use Sentinel\Project\Domain\Event\ProjectCreated;
use Sentinel\Project\Domain\Exception\InvalidProject;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Project\Domain\ValueObject\ProjectStatus;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\DomainEvent;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class Project
{
    private function __construct(
        private ProjectId $id,
        private OrganizationId $organizationId,
        private string $name,
        private string $slug,
        private ?string $description,
        private ProjectStatus $status,
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
        string $name,
        string $slug,
        ?string $description,
        string $createdByIdentityId,
        DateTimeImmutable $at,
        string $eventId,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        $project = self::fromValues(
            id: $id,
            organizationId: $organizationId,
            name: $name,
            slug: $slug,
            description: $description,
            status: ProjectStatus::ACTIVE->value,
            createdByIdentityId: $createdByIdentityId,
            createdAt: $at,
            updatedAt: $at,
            archivedAt: null,
            version: 1,
        );

        return $project->withEvent(ProjectCreated::fromValues(
            eventId: $eventId,
            organizationId: $organizationId,
            projectId: $id,
            occurredAt: $at,
            correlationId: $correlationId,
            causationId: $causationId,
            actorIdentityId: $createdByIdentityId,
            projectName: $project->name,
            projectSlug: $project->slug,
            projectDescription: $project->description,
            projectStatus: $project->status,
            aggregateVersion: $project->version->value(),
        ));
    }

    public static function fromValues(
        string $id,
        string $organizationId,
        string $name,
        string $slug,
        ?string $description,
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
            throw InvalidProject::forField('name');
        }

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $normalizedSlug) !== 1 || mb_strlen($normalizedSlug) > 255) {
            throw InvalidProject::forField('slug');
        }

        if ($normalizedDescription === '') {
            $normalizedDescription = null;
        }

        return new self(
            id: ProjectId::fromString($id),
            organizationId: OrganizationId::fromString($organizationId),
            name: $normalizedName,
            slug: $normalizedSlug,
            description: $normalizedDescription,
            status: ProjectStatus::fromString($status),
            createdByIdentityId: IdentityId::fromString($createdByIdentityId),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            archivedAt: $archivedAt,
            version: ResourceVersion::fromInt($version),
        );
    }

    public function id(): ProjectId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
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

    public function status(): ProjectStatus
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
