<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Policy;

use Sentinel\Event\Application\Policy\EventRetrievalPolicy;
use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ActiveMembershipWithPermissionMayGetEventPolicy implements EventRetrievalPolicy
{
    public function __construct(private OrganizationPermissionAuthorizer $authorizer) {}

    public function allows(TenantContext $tenantContext): bool
    {
        return $this->authorizer->allows($tenantContext, CorePermissions::readEvents());
    }
}
