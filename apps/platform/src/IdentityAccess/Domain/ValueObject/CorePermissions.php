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
            self::archiveApplication(),
            self::createEnvironment(),
            self::readEnvironments(),
            self::archiveEnvironment(),
            self::createEventSource(),
            self::revokeEventSourceCredential(),
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

    public static function archiveApplication(): PermissionCode
    {
        return PermissionCode::fromString('application.archive');
    }

    public static function createEnvironment(): PermissionCode
    {
        return PermissionCode::fromString('environment.create');
    }

    public static function readEnvironments(): PermissionCode
    {
        return PermissionCode::fromString('environment.read');
    }

    public static function archiveEnvironment(): PermissionCode
    {
        return PermissionCode::fromString('environment.archive');
    }

    public static function createEventSource(): PermissionCode
    {
        return PermissionCode::fromString('event_source.create');
    }

    public static function revokeEventSourceCredential(): PermissionCode
    {
        return PermissionCode::fromString('event_source.credential.revoke');
    }
}
