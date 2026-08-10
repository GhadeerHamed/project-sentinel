<?php

declare(strict_types=1);

namespace App\Infrastructure\Application\Policy;

use Sentinel\Application\Application\Policy\ApplicationArchivalPolicy;
use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ActiveMembershipWithPermissionMayArchiveApplicationPolicy implements ApplicationArchivalPolicy
{
    public function __construct(private OrganizationPermissionAuthorizer $authorizer) {}

    public function allows(TenantContext $tenantContext): bool
    {
        return $this->authorizer->allows($tenantContext, CorePermissions::archiveApplication());
    }
}
