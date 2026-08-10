<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Result;

use DateTimeImmutable;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\Entity\Environment;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentStatus;
use Sentinel\Environment\Domain\ValueObject\EnvironmentType;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class CreateEnvironmentResult
{
    private function __construct(
        private EnvironmentId $environmentId,
        private OrganizationId $organizationId,
        private ApplicationId $applicationId,
        private string $name,
        private string $slug,
        private EnvironmentType $environmentType,
        private EnvironmentStatus $status,
        private IdentityId $createdByIdentityId,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $archivedAt,
        private ResourceVersion $version,
    ) {}

    public static function fromEnvironment(Environment $environment): self
    {
        return new self(
            environmentId: $environment->id(),
            organizationId: $environment->organizationId(),
            applicationId: $environment->applicationId(),
            name: $environment->name(),
            slug: $environment->slug(),
            environmentType: $environment->environmentType(),
            status: $environment->status(),
            createdByIdentityId: $environment->createdByIdentityId(),
            createdAt: $environment->createdAt(),
            updatedAt: $environment->updatedAt(),
            archivedAt: $environment->archivedAt(),
            version: $environment->version(),
        );
    }

    public function environmentId(): EnvironmentId
    {
        return $this->environmentId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function applicationId(): ApplicationId
    {
        return $this->applicationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function environmentType(): EnvironmentType
    {
        return $this->environmentType;
    }

    public function status(): EnvironmentStatus
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
