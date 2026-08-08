<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Policy;

use Sentinel\Shared\Domain\Tenant\TenantContext;

interface RoleRevocationPolicy
{
    public function allows(TenantContext $tenantContext): bool;
}
