<?php

declare(strict_types=1);

namespace Tests\Unit\IdentityAccess\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Sentinel\IdentityAccess\Domain\Entity\RoleAssignment;
use Sentinel\IdentityAccess\Domain\Exception\CrossOrganizationAssignment;
use Sentinel\IdentityAccess\Domain\Repository\PermissionAssignmentRepository;
use Sentinel\IdentityAccess\Domain\Repository\RoleAssignmentRepository;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class RolePermissionContractsTest extends TestCase
{
    #[Test]
    public function it_defines_explicit_role_and_permission_assignment_contracts(): void
    {
        $roleAssignments = new ReflectionClass(RoleAssignmentRepository::class);
        $permissions = new ReflectionClass(PermissionAssignmentRepository::class);

        self::assertTrue($roleAssignments->isInterface());
        self::assertTrue($permissions->isInterface());

        $activeForIdentity = $roleAssignments->getMethod('activeForIdentity');
        $parameters = $activeForIdentity->getParameters();

        self::assertInstanceOf(ReflectionNamedType::class, $parameters[0]->getType());
        self::assertSame(OrganizationId::class, $parameters[0]->getType()->getName());

        $permissionsForIdentity = $permissions->getMethod('permissionsForIdentity');
        $permissionsParameters = $permissionsForIdentity->getParameters();

        self::assertInstanceOf(ReflectionNamedType::class, $permissionsParameters[0]->getType());
        self::assertSame(OrganizationId::class, $permissionsParameters[0]->getType()->getName());
    }

    #[Test]
    public function it_rejects_cross_organization_role_assignments(): void
    {
        $this->expectException(CrossOrganizationAssignment::class);

        RoleAssignment::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e01',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e02',
            identityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e03',
            roleId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e04',
            roleOrganizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e99',
            assignedByIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e05',
            assignedAt: new DateTimeImmutable('2026-08-02T14:40:00+00:00'),
        );
    }
}
