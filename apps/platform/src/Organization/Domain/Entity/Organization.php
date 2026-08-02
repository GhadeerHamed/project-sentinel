<?php

declare(strict_types=1);

namespace Sentinel\Organization\Domain\Entity;

use DateTimeImmutable;
use Sentinel\Organization\Domain\Exception\InvalidOrganization;
use Sentinel\Organization\Domain\Exception\InvalidOrganizationTransition;
use Sentinel\Organization\Domain\ValueObject\OrganizationStatus;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class Organization
{
    private function __construct(
        private OrganizationId $id,
        private string $name,
        private string $slug,
        private OrganizationStatus $status,
        private IdentityId $createdByIdentityId,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ResourceVersion $version,
    ) {}

    public static function fromValues(
        string $id,
        string $name,
        string $slug,
        string $status,
        string $createdByIdentityId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        $normalizedName = trim($name);
        $normalizedSlug = strtolower(trim($slug));

        if ($normalizedName === '') {
            throw InvalidOrganization::forField('name');
        }

        if ($normalizedSlug === '') {
            throw InvalidOrganization::forField('slug');
        }

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $normalizedSlug) !== 1) {
            throw InvalidOrganization::forField('slug');
        }

        return new self(
            id: OrganizationId::fromString($id),
            name: $normalizedName,
            slug: $normalizedSlug,
            status: OrganizationStatus::fromString($status),
            createdByIdentityId: IdentityId::fromString($createdByIdentityId),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            version: ResourceVersion::fromInt($version),
        );
    }

    public function id(): OrganizationId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function status(): OrganizationStatus
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

    public function version(): ResourceVersion
    {
        return $this->version;
    }

    public function suspend(DateTimeImmutable $at): self
    {
        if ($this->status !== OrganizationStatus::ACTIVE) {
            throw InvalidOrganizationTransition::fromTo($this->status, OrganizationStatus::SUSPENDED);
        }

        return $this->withStatus(OrganizationStatus::SUSPENDED, $at);
    }

    public function reactivate(DateTimeImmutable $at): self
    {
        if ($this->status !== OrganizationStatus::SUSPENDED) {
            throw InvalidOrganizationTransition::fromTo($this->status, OrganizationStatus::ACTIVE);
        }

        return $this->withStatus(OrganizationStatus::ACTIVE, $at);
    }

    public function archive(DateTimeImmutable $at): self
    {
        if (
            $this->status !== OrganizationStatus::ACTIVE
            && $this->status !== OrganizationStatus::SUSPENDED
        ) {
            throw InvalidOrganizationTransition::fromTo($this->status, OrganizationStatus::ARCHIVED);
        }

        return $this->withStatus(OrganizationStatus::ARCHIVED, $at);
    }

    private function withStatus(OrganizationStatus $status, DateTimeImmutable $updatedAt): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            status: $status,
            createdByIdentityId: $this->createdByIdentityId,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            version: $this->version->next(),
        );
    }
}
