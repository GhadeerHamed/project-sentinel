<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Policy;

use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\Organization\Application\Policy\OrganizationUpdatePolicy;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ActiveMembershipWithPermissionMayUpdateOrganizationPolicy implements OrganizationUpdatePolicy
{
    private const PERMISSION = 'organization.update';

    public function __construct(private OrganizationPermissionAuthorizer $authorizer) {}

    public function allows(TenantContext $tenantContext): bool
    {
        return $this->authorizer->allows($tenantContext, PermissionCode::fromString(self::PERMISSION));
    }
}
