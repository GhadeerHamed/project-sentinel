<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Policy;

use Sentinel\Shared\Domain\Identity\IdentityReference;

interface OrganizationCreationPolicy
{
    public function allows(IdentityReference $creator): bool;
}
