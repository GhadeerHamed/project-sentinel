<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Policy;

use Sentinel\Event\Application\Policy\EventDetailVisibilityPolicy;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class EventReadPermissionGrantsDetailVisibilityPolicy implements EventDetailVisibilityPolicy
{
    public function permitsPayloadAndAttributes(TenantContext $tenantContext): bool
    {
        return true;
    }
}
