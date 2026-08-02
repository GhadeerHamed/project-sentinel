<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;

final class InvalidRoleAssignmentTransition extends DomainException
{
    public static function alreadyRevoked(): self
    {
        return new self('Role assignment is already revoked.');
    }
}
