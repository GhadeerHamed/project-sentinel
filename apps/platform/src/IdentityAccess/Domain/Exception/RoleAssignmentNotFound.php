<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class RoleAssignmentNotFound extends DomainException
{
    public static function forId(RoleAssignmentId $assignmentId, OrganizationId $organizationId): self
    {
        return new self(sprintf('Role assignment %s was not found in organization %s.', $assignmentId->value(), $organizationId->value()));
    }
}
