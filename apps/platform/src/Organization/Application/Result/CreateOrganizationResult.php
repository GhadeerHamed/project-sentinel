<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Result;

use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class CreateOrganizationResult
{
    private function __construct(
        private OrganizationId $organizationId,
        private MembershipId $creatorMembershipId,
        private RoleAssignmentId $initialRoleAssignmentId,
    ) {}

    public static function fromValues(
        string $organizationId,
        string $creatorMembershipId,
        string $initialRoleAssignmentId,
    ): self {
        return new self(
            organizationId: OrganizationId::fromString($organizationId),
            creatorMembershipId: MembershipId::fromString($creatorMembershipId),
            initialRoleAssignmentId: RoleAssignmentId::fromString($initialRoleAssignmentId),
        );
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function creatorMembershipId(): MembershipId
    {
        return $this->creatorMembershipId;
    }

    public function initialRoleAssignmentId(): RoleAssignmentId
    {
        return $this->initialRoleAssignmentId;
    }
}
