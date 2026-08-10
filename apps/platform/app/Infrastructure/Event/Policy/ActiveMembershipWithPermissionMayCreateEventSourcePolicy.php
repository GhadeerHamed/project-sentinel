<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Policy;

use Sentinel\Event\Application\Policy\EventSourceRegistrationPolicy;
use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ActiveMembershipWithPermissionMayCreateEventSourcePolicy implements EventSourceRegistrationPolicy
{
    public function __construct(private OrganizationPermissionAuthorizer $authorizer) {}

    public function allows(TenantContext $tenantContext): bool
    {
        return $this->authorizer->allows($tenantContext, CorePermissions::createEventSource());
    }
}
