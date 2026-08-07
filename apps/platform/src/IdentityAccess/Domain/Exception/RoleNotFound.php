<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class RoleNotFound extends DomainException
{
    public static function forId(RoleId $roleId, OrganizationId $organizationId): self
    {
        return new self(sprintf(
            'Role %s was not found in organization %s.',
            $roleId->value(),
            $organizationId->value(),
        ));
    }
}
