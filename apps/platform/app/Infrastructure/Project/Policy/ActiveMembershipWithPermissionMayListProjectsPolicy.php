<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Policy;

use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\Project\Application\Policy\ProjectListingPolicy;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ActiveMembershipWithPermissionMayListProjectsPolicy implements ProjectListingPolicy
{
    public function __construct(private OrganizationPermissionAuthorizer $authorizer) {}

    public function allows(TenantContext $tenantContext): bool
    {
        return $this->authorizer->allows($tenantContext, CorePermissions::readProjects());
    }
}
