<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Policy;

use Sentinel\Organization\Application\Policy\OrganizationCreationPolicy;
use Sentinel\Shared\Domain\Identity\IdentityReference;

final class AllowAllOrganizationCreationPolicy implements OrganizationCreationPolicy
{
    public function allows(IdentityReference $creator): bool
    {
        return true;
    }
}
