<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Policy;

use Sentinel\Shared\Domain\Tenant\TenantContext;

interface ApplicationCreationPolicy
{
    public function allows(TenantContext $tenantContext): bool;
}
