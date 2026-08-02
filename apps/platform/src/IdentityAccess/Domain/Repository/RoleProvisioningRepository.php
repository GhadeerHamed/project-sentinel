<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Repository;

use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleKey;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface RoleProvisioningRepository
{
    public function provisionRole(OrganizationId $organizationId, RoleKey $roleKey): RoleId;
}
