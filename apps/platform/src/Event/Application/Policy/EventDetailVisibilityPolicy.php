<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Policy;

use Sentinel\Shared\Domain\Tenant\TenantContext;

interface EventDetailVisibilityPolicy
{
    public function permitsPayloadAndAttributes(TenantContext $tenantContext): bool;
}
