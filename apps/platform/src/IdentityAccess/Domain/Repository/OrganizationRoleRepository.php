<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Repository;

use Sentinel\IdentityAccess\Domain\Entity\OrganizationRole;
use Sentinel\IdentityAccess\Domain\Exception\RoleNotFound;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface OrganizationRoleRepository
{
    /**
     * @throws RoleNotFound
     */
    public function getById(RoleId $roleId, OrganizationId $organizationId): OrganizationRole;
}
