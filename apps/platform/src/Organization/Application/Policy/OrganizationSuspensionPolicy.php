<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Policy;

use Sentinel\Shared\Domain\Tenant\TenantContext;

interface OrganizationSuspensionPolicy
{
    public function allows(TenantContext $tenantContext): bool;
}
