<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Entity;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\Exception\CrossOrganizationAssignment;
use Sentinel\IdentityAccess\Domain\Exception\InvalidRoleAssignmentTransition;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class RoleAssignment
{
    private function __construct(
        private RoleAssignmentId $id,
        private OrganizationId $organizationId,
        private IdentityId $identityId,
        private RoleId $roleId,
        private IdentityId $assignedByIdentityId,
        private DateTimeImmutable $assignedAt,
        private ?DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $revokedAt,
        private ?IdentityId $revokedByIdentityId,
    ) {}

    public static function fromValues(
        string $id,
        string $organizationId,
        string $identityId,
        string $roleId,
        string $roleOrganizationId,
        string $assignedByIdentityId,
        DateTimeImmutable $assignedAt,
        ?DateTimeImmutable $expiresAt = null,
    ): self {
        if (strtolower($organizationId) !== strtolower($roleOrganizationId)) {
            throw CrossOrganizationAssignment::forRoleAssignment();
        }

        return new self(
            id: RoleAssignmentId::fromString($id),
            organizationId: OrganizationId::fromString($organizationId),
            identityId: IdentityId::fromString($identityId),
            roleId: RoleId::fromString($roleId),
            assignedByIdentityId: IdentityId::fromString($assignedByIdentityId),
            assignedAt: $assignedAt,
            expiresAt: $expiresAt,
            revokedAt: null,
            revokedByIdentityId: null,
        );
    }

    public function id(): RoleAssignmentId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function identityId(): IdentityId
    {
        return $this->identityId;
    }

    public function roleId(): RoleId
    {
        return $this->roleId;
    }

    public function assignedByIdentityId(): IdentityId
    {
        return $this->assignedByIdentityId;
    }

    public function assignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function revokedByIdentityId(): ?IdentityId
    {
        return $this->revokedByIdentityId;
    }

    public function revoke(DateTimeImmutable $revokedAt, string $revokedByIdentityId): self
    {
        if ($this->revokedAt !== null) {
            throw InvalidRoleAssignmentTransition::alreadyRevoked();
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            identityId: $this->identityId,
            roleId: $this->roleId,
            assignedByIdentityId: $this->assignedByIdentityId,
            assignedAt: $this->assignedAt,
            expiresAt: $this->expiresAt,
            revokedAt: $revokedAt,
            revokedByIdentityId: IdentityId::fromString($revokedByIdentityId),
        );
    }

    public function isActiveAt(DateTimeImmutable $at): bool
    {
        if ($this->revokedAt !== null && $this->revokedAt <= $at) {
            return false;
        }

        if ($this->expiresAt !== null && $this->expiresAt <= $at) {
            return false;
        }

        return true;
    }
}
