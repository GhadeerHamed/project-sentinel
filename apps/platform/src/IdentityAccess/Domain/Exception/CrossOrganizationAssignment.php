<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;

final class CrossOrganizationAssignment extends DomainException
{
    public static function forRoleAssignment(): self
    {
        return new self('Cross-organization role assignments are prohibited.');
    }

    public static function forMembershipScope(): self
    {
        return new self('Membership access outside its organization scope is prohibited.');
    }
}
