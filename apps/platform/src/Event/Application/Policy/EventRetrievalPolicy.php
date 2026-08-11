<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Policy;

use Sentinel\Shared\Domain\Tenant\TenantContext;

interface EventRetrievalPolicy
{
    public function allows(TenantContext $tenantContext): bool;
}
