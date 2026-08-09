<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class AdministrativeAccessInvariantViolation extends DomainException
{
    public static function forOrganization(OrganizationId $organizationId): self
    {
        return new self(sprintf(
            'Organization %s would no longer have an active human identity with administrative recovery access.',
            $organizationId->value(),
        ));
    }
}
