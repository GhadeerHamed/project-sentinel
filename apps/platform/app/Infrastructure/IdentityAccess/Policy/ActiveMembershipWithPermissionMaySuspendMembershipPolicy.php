<?php

declare(strict_types=1);

namespace App\Infrastructure\IdentityAccess\Policy;

use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Application\Policy\MembershipSuspensionPolicy;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ActiveMembershipWithPermissionMaySuspendMembershipPolicy implements MembershipSuspensionPolicy
{
    private const PERMISSION = 'organization.member.manage';

    public function __construct(private OrganizationPermissionAuthorizer $authorizer) {}

    public function allows(TenantContext $tenantContext): bool
    {
        return $this->authorizer->allows($tenantContext, PermissionCode::fromString(self::PERMISSION));
    }
}
