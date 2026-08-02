<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Repository;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface PermissionAssignmentRepository
{
    public function assignToRole(
        OrganizationId $organizationId,
        RoleId $roleId,
        PermissionCode $permissionCode,
        IdentityId $grantedBy,
        DateTimeImmutable $grantedAt,
    ): void;

    public function revokeFromRole(
        OrganizationId $organizationId,
        RoleId $roleId,
        PermissionCode $permissionCode,
        IdentityId $revokedBy,
        DateTimeImmutable $revokedAt,
    ): void;

    /**
     * @return list<PermissionCode>
     */
    public function permissionsForIdentity(
        OrganizationId $organizationId,
        IdentityId $identityId,
        DateTimeImmutable $at,
    ): array;
}
