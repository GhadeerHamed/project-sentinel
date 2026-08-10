<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Result;

use DateTimeImmutable;
use Sentinel\Application\Domain\Entity\Application;
use Sentinel\Application\Domain\ValueObject\ApplicationCriticality;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Application\Domain\ValueObject\ApplicationStatus;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class CreateApplicationResult
{
    private function __construct(
        private ApplicationId $applicationId,
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
    ) {}

    public static function fromApplication(Application $application): self
    {
        return new self(
            applicationId: $application->id(),
            organizationId: $application->organizationId(),
            projectId: $application->projectId(),
            name: $application->name(),
            slug: $application->slug(),
            description: $application->description(),
            criticality: $application->criticality(),
            status: $application->status(),
            createdByIdentityId: $application->createdByIdentityId(),
            createdAt: $application->createdAt(),
            updatedAt: $application->updatedAt(),
            archivedAt: $application->archivedAt(),
            version: $application->version(),
        );
    }

    public function applicationId(): ApplicationId
    {
        return $this->applicationId;
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
}
