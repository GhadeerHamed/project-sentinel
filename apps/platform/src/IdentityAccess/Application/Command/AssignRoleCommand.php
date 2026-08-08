<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Command;

use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;

final readonly class AssignRoleCommand
{
    public function __construct(
        private MembershipId $membershipId,
        private RoleId $roleId,
    ) {}

    public function membershipId(): MembershipId
    {
        return $this->membershipId;
    }

    public function roleId(): RoleId
    {
        return $this->roleId;
    }
}
