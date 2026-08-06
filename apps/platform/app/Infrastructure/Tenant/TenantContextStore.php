<?php

declare(strict_types=1);

namespace App\Infrastructure\Tenant;

use LogicException;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class TenantContextStore
{
    private ?TenantContext $tenantContext = null;

    public function set(TenantContext $tenantContext): void
    {
        $this->tenantContext = $tenantContext;
    }

    public function tenantContext(): TenantContext
    {
        return $this->tenantContext ?? throw new LogicException('Tenant context has not been resolved.');
    }
}
