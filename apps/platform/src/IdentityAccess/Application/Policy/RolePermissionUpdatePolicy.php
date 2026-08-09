<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Policy;

use Sentinel\Shared\Domain\Tenant\TenantContext;

interface RolePermissionUpdatePolicy
{
    public function allows(TenantContext $tenantContext): bool;
}
