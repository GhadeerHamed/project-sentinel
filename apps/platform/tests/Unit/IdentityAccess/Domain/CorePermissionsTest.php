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

        self::assertInstanceOf(PermissionCode::class, $memberManagement);
        self::assertSame('organization.member.manage', $memberManagement->value());
        self::assertInstanceOf(PermissionCode::class, $organizationUpdate);
        self::assertSame('organization.update', $organizationUpdate->value());
        self::assertInstanceOf(PermissionCode::class, $projectCreate);
        self::assertSame('project.create', $projectCreate->value());
        self::assertSame(
            ['organization.member.manage', 'organization.update', 'organization.suspend', 'project.create'],
            CorePermissions::values(),
        );
    }
}
