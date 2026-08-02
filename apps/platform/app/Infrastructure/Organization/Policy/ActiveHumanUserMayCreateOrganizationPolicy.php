<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Policy;

use Sentinel\Organization\Application\Policy\OrganizationCreationPolicy;
use Sentinel\Shared\Domain\Identity\IdentityReference;
use Sentinel\Shared\Domain\Identity\IdentityType;

final class ActiveHumanUserMayCreateOrganizationPolicy implements OrganizationCreationPolicy
{
    public function allows(IdentityReference $creator): bool
    {
        return $creator->identityType() === IdentityType::USER->value;
    }
}
