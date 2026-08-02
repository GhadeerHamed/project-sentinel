<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class MembershipNotFound extends DomainException
{
    public static function forId(MembershipId $membershipId, OrganizationId $organizationId): self
    {
        return new self(sprintf(
            'Membership %s was not found in organization %s.',
            $membershipId->value(),
            $organizationId->value(),
        ));
    }

    public static function forIdentity(IdentityId $identityId, OrganizationId $organizationId): self
    {
        return new self(sprintf(
            'Membership for identity %s was not found in organization %s.',
            $identityId->value(),
            $organizationId->value(),
        ));
    }
}
