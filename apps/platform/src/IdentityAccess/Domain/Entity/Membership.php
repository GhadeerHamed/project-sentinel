<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Entity;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\Exception\CrossOrganizationAssignment;
use Sentinel\IdentityAccess\Domain\Exception\InvalidMembershipTransition;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipStatus;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class Membership
{
    private function __construct(
        private MembershipId $id,
        private OrganizationId $organizationId,
        private IdentityId $userIdentityId,
        private MembershipStatus $status,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $activatedAt,
        private ?DateTimeImmutable $suspendedAt,
        private ?DateTimeImmutable $removedAt,
    ) {}

    public static function fromValues(
        string $id,
        string $organizationId,
        string $userIdentityId,
        string $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $activatedAt = null,
        ?DateTimeImmutable $suspendedAt = null,
        ?DateTimeImmutable $removedAt = null,
    ): self {
        $membershipStatus = MembershipStatus::fromString($status);

        if ($membershipStatus === MembershipStatus::ACTIVE && $activatedAt === null) {
            throw InvalidMembershipTransition::fromTo(MembershipStatus::PENDING, MembershipStatus::ACTIVE);
        }

        if ($membershipStatus === MembershipStatus::SUSPENDED && $suspendedAt === null) {
            throw InvalidMembershipTransition::fromTo(MembershipStatus::ACTIVE, MembershipStatus::SUSPENDED);
        }

        if ($membershipStatus === MembershipStatus::REMOVED && $removedAt === null) {
            throw InvalidMembershipTransition::fromTo(MembershipStatus::ACTIVE, MembershipStatus::REMOVED);
        }

        return new self(
            id: MembershipId::fromString($id),
            organizationId: OrganizationId::fromString($organizationId),
            userIdentityId: IdentityId::fromString($userIdentityId),
            status: $membershipStatus,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            activatedAt: $activatedAt,
            suspendedAt: $suspendedAt,
            removedAt: $removedAt,
        );
    }

    public function id(): MembershipId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function userIdentityId(): IdentityId
    {
        return $this->userIdentityId;
    }

    public function status(): MembershipStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function activatedAt(): ?DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function suspendedAt(): ?DateTimeImmutable
    {
        return $this->suspendedAt;
    }

    public function removedAt(): ?DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function grantsOrganizationAccess(): bool
    {
        return $this->status->grantsAccess();
    }

    public function assertOrganizationScope(string $organizationId): void
    {
        if (! $this->organizationId->equals(OrganizationId::fromString($organizationId))) {
            throw CrossOrganizationAssignment::forMembershipScope();
        }
    }

    public function activate(DateTimeImmutable $at): self
    {
        if (
            $this->status !== MembershipStatus::INVITED
            && $this->status !== MembershipStatus::PENDING
            && $this->status !== MembershipStatus::SUSPENDED
        ) {
            throw InvalidMembershipTransition::fromTo($this->status, MembershipStatus::ACTIVE);
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userIdentityId: $this->userIdentityId,
            status: MembershipStatus::ACTIVE,
            createdAt: $this->createdAt,
            updatedAt: $at,
            activatedAt: $at,
            suspendedAt: null,
            removedAt: $this->removedAt,
        );
    }

    public function suspend(DateTimeImmutable $at): self
    {
        if ($this->status !== MembershipStatus::ACTIVE) {
            throw InvalidMembershipTransition::fromTo($this->status, MembershipStatus::SUSPENDED);
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userIdentityId: $this->userIdentityId,
            status: MembershipStatus::SUSPENDED,
            createdAt: $this->createdAt,
            updatedAt: $at,
            activatedAt: $this->activatedAt,
            suspendedAt: $at,
            removedAt: $this->removedAt,
        );
    }

    public function remove(DateTimeImmutable $at): self
    {
        if ($this->status === MembershipStatus::REMOVED) {
            throw InvalidMembershipTransition::fromTo($this->status, MembershipStatus::REMOVED);
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userIdentityId: $this->userIdentityId,
            status: MembershipStatus::REMOVED,
            createdAt: $this->createdAt,
            updatedAt: $at,
            activatedAt: $this->activatedAt,
            suspendedAt: $this->suspendedAt,
            removedAt: $at,
        );
    }
}
