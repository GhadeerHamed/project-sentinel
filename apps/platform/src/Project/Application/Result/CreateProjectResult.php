<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Result;

use DateTimeImmutable;
use Sentinel\Project\Domain\Entity\Project;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Project\Domain\ValueObject\ProjectStatus;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class CreateProjectResult
{
    private function __construct(
        private ProjectId $projectId,
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
    ) {}

    public static function fromProject(Project $project): self
    {
        return new self(
            projectId: $project->id(),
            organizationId: $project->organizationId(),
            name: $project->name(),
            slug: $project->slug(),
            description: $project->description(),
            status: $project->status(),
            createdByIdentityId: $project->createdByIdentityId(),
            createdAt: $project->createdAt(),
            updatedAt: $project->updatedAt(),
            archivedAt: $project->archivedAt(),
            version: $project->version(),
        );
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
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
}
