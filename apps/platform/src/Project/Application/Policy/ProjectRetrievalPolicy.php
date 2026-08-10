<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Policy;

use Sentinel\Shared\Domain\Tenant\TenantContext;

interface ProjectRetrievalPolicy
{
    public function allows(TenantContext $tenantContext): bool;
}
