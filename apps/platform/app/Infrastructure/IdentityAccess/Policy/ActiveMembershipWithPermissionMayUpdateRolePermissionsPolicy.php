<?php

declare(strict_types=1);

namespace App\Infrastructure\IdentityAccess\Policy;

use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Application\Policy\RolePermissionUpdatePolicy;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ActiveMembershipWithPermissionMayUpdateRolePermissionsPolicy implements RolePermissionUpdatePolicy
{
    public function __construct(private OrganizationPermissionAuthorizer $authorizer) {}

    public function allows(TenantContext $tenantContext): bool
    {
        return $this->authorizer->allows($tenantContext, CorePermissions::manageOrganizationMembers());
    }
}
