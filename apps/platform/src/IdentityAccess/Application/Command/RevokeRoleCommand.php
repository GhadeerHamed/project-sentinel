<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Command;

use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;

final readonly class RevokeRoleCommand
{
    public function __construct(private RoleAssignmentId $assignmentId) {}

    public function assignmentId(): RoleAssignmentId
    {
        return $this->assignmentId;
    }
}
