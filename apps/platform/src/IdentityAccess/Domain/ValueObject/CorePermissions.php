<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\ValueObject;

final class CorePermissions
{
    /**
     * @return list<PermissionCode>
     */
    public static function all(): array
    {
        return [
            self::manageOrganizationMembers(),
            self::updateOrganization(),
            self::suspendOrganization(),
            self::createProject(),
            self::readProjects(),
            self::archiveProject(),
            self::createApplication(),
            self::readApplications(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (PermissionCode $permission): string => $permission->value(), self::all());
    }

    public static function manageOrganizationMembers(): PermissionCode
    {
        return PermissionCode::fromString('organization.member.manage');
    }

    public static function updateOrganization(): PermissionCode
    {
        return PermissionCode::fromString('organization.update');
    }

    public static function suspendOrganization(): PermissionCode
    {
        return PermissionCode::fromString('organization.suspend');
    }

    public static function createProject(): PermissionCode
    {
        return PermissionCode::fromString('project.create');
    }

    public static function readProjects(): PermissionCode
    {
        return PermissionCode::fromString('project.read');
    }

    public static function archiveProject(): PermissionCode
    {
        return PermissionCode::fromString('project.archive');
    }

    public static function createApplication(): PermissionCode
    {
        return PermissionCode::fromString('application.create');
    }

    public static function readApplications(): PermissionCode
    {
        return PermissionCode::fromString('application.read');
    }
}
