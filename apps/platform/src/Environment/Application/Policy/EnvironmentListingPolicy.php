<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Policy;

use Sentinel\Shared\Domain\Tenant\TenantContext;

interface EnvironmentListingPolicy
{
    public function allows(TenantContext $tenantContext): bool;
}
