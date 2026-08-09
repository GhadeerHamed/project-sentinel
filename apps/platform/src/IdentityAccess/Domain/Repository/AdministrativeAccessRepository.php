<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Repository;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface AdministrativeAccessRepository
{
    public function lockOrganizationForAdministrativeAccessMutation(OrganizationId $organizationId): void;

    public function hasActiveHumanIdentityWithEffectivePermission(
        OrganizationId $organizationId,
        PermissionCode $permissionCode,
        DateTimeImmutable $at,
    ): bool;
}
