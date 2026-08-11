<?php

declare(strict_types=1);

namespace Tests\Unit\IdentityAccess\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;

final class CorePermissionsTest extends TestCase
{
    #[Test]
    public function it_exposes_the_current_canonical_sentinel_permission_codes(): void
    {
        $memberManagement = CorePermissions::manageOrganizationMembers();
        $organizationUpdate = CorePermissions::updateOrganization();
        $projectCreate = CorePermissions::createProject();
        $projectRead = CorePermissions::readProjects();
        $projectArchive = CorePermissions::archiveProject();
        $applicationCreate = CorePermissions::createApplication();
        $applicationRead = CorePermissions::readApplications();
        $environmentCreate = CorePermissions::createEnvironment();
        $environmentRead = CorePermissions::readEnvironments();
        $environmentArchive = CorePermissions::archiveEnvironment();
        $eventSourceCreate = CorePermissions::createEventSource();
        $eventSourceCredentialRevoke = CorePermissions::revokeEventSourceCredential();
        $eventRead = CorePermissions::readEvents();

        self::assertInstanceOf(PermissionCode::class, $memberManagement);
        self::assertSame('organization.member.manage', $memberManagement->value());
        self::assertInstanceOf(PermissionCode::class, $organizationUpdate);
        self::assertSame('organization.update', $organizationUpdate->value());
        self::assertInstanceOf(PermissionCode::class, $projectCreate);
        self::assertSame('project.create', $projectCreate->value());
        self::assertInstanceOf(PermissionCode::class, $projectRead);
        self::assertSame('project.read', $projectRead->value());
        self::assertInstanceOf(PermissionCode::class, $projectArchive);
        self::assertSame('project.archive', $projectArchive->value());
        self::assertInstanceOf(PermissionCode::class, $applicationCreate);
        self::assertSame('application.create', $applicationCreate->value());
        self::assertInstanceOf(PermissionCode::class, $applicationRead);
        self::assertSame('application.read', $applicationRead->value());
        self::assertInstanceOf(PermissionCode::class, $environmentCreate);
        self::assertSame('environment.create', $environmentCreate->value());
        self::assertInstanceOf(PermissionCode::class, $environmentRead);
        self::assertSame('environment.read', $environmentRead->value());
        self::assertInstanceOf(PermissionCode::class, $environmentArchive);
        self::assertSame('environment.archive', $environmentArchive->value());
        self::assertInstanceOf(PermissionCode::class, $eventSourceCreate);
        self::assertSame('event_source.create', $eventSourceCreate->value());
        self::assertSame('event_source.credential.revoke', $eventSourceCredentialRevoke->value());
        self::assertSame('event.read', $eventRead->value());
        self::assertSame(
            ['organization.member.manage', 'organization.update', 'organization.suspend', 'project.create', 'project.read', 'project.archive', 'application.create', 'application.read', 'application.archive', 'environment.create', 'environment.read', 'environment.archive', 'event_source.create', 'event_source.credential.revoke', 'event.read'],
            CorePermissions::values(),
        );
    }
}
