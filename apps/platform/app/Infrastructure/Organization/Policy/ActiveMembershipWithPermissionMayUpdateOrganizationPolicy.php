<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Policy;

use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\PermissionAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\Organization\Application\Policy\OrganizationUpdatePolicy;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;

final readonly class ActiveMembershipWithPermissionMayUpdateOrganizationPolicy implements OrganizationUpdatePolicy
{
    private const PERMISSION = 'organization.update';

    public function __construct(private MembershipRepository $memberships, private PermissionAssignmentRepository $permissions, private Clock $clock) {}

    public function allows(TenantContext $tenantContext): bool
    {
        $identityId = IdentityId::fromString($tenantContext->actingIdentity()->identityId());

        try {
            $membership = $this->memberships->getByIdentity($tenantContext->organizationId(), $identityId);
        } catch (MembershipNotFound) {
            return false;
        }

        if (! $membership->grantsOrganizationAccess()) {
            return false;
        }

        $requiredPermission = PermissionCode::fromString(self::PERMISSION);

        return array_any($this->permissions->permissionsForIdentity($tenantContext->organizationId(), $identityId, $this->clock->now()), fn ($permission) => $permission->equals($requiredPermission));

    }
}
