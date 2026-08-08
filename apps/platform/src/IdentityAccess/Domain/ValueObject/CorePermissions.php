<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\ValueObject;

final class CorePermissions
{
    public static function manageOrganizationMembers(): PermissionCode
    {
        return PermissionCode::fromString('organization.member.manage');
    }

    public static function updateOrganization(): PermissionCode
    {
        return PermissionCode::fromString('organization.update');
    }
}
