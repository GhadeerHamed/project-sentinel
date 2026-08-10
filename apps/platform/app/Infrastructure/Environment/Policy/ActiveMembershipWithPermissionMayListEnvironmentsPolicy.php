<?php

declare(strict_types=1);

namespace App\Infrastructure\Environment\Policy;

use Sentinel\Environment\Application\Policy\EnvironmentListingPolicy;
use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ActiveMembershipWithPermissionMayListEnvironmentsPolicy implements EnvironmentListingPolicy
{
    public function __construct(private OrganizationPermissionAuthorizer $authorizer) {}

    public function allows(TenantContext $tenantContext): bool
    {
        return $this->authorizer->allows($tenantContext, CorePermissions::readEnvironments());
    }
}
