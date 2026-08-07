<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Organization;

use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlPermissionAssignmentRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\IdentityAccess\Domain\Exception\PermissionNotFound;
use Sentinel\IdentityAccess\Domain\Exception\RoleNotFound;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Tests\TestCase;

final class UpdateOrganizationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_permissions_assigned_within_the_requested_organization_only(): void
    {
        $organizationId = $this->uuid();
        $otherOrganizationId = $this->uuid();
        $identityId = $this->uuid();
        $permissionId = $this->uuid();
        $roleId = $this->uuid();
        $otherRoleId = $this->uuid();
        $now = new DateTimeImmutable('2026-08-07T00:00:00+00:00');
        foreach ([$organizationId, $otherOrganizationId] as $id) {
            DB::table('organizations')->insert(['id' => $id, 'name' => 'Organization', 'slug' => 'organization-'.$id, 'created_by_identity_id' => $identityId, 'status' => 'active', 'version' => 1, 'timezone' => 'UTC', 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('permissions')->insert(['id' => $permissionId, 'code' => 'organization.update', 'capability' => 'organization', 'action' => 'update', 'description' => 'Update organization profiles.', 'risk_level' => 'medium', 'created_at' => $now]);
        foreach ([[$organizationId, $roleId], [$otherOrganizationId, $otherRoleId]] as [$organization, $role]) {
            DB::table('organization_roles')->insert(['id' => $role, 'organization_id' => $organization, 'role_key' => 'operator-'.$role, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organization, 'identity_id' => $identityId, 'role_id' => $role, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);

        $permissions = (new PgsqlPermissionAssignmentRepository)->permissionsForIdentity(OrganizationId::fromString($organizationId), IdentityId::fromString($identityId), $now);

        self::assertSame(['organization.update'], array_map(static fn ($permission): string => $permission->value(), $permissions));
    }

    #[Test]
    public function it_rejects_assigning_or_revoking_permissions_for_a_role_from_another_organization(): void
    {
        $organizationId = $this->uuid();
        $otherOrganizationId = $this->uuid();
        $identityId = $this->uuid();
        $roleId = $this->uuid();
        $permissionId = $this->uuid();
        $now = new DateTimeImmutable('2026-08-07T00:00:00+00:00');
        $this->insertOrganization($organizationId, $identityId, $now);
        $this->insertOrganization($otherOrganizationId, $identityId, $now);
        $this->insertRole($otherOrganizationId, $roleId, $now);
        $this->insertPermission($permissionId, $now);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);
        $repository = new PgsqlPermissionAssignmentRepository;

        try {
            $repository->assignToRole(OrganizationId::fromString($organizationId), RoleId::fromString($roleId), PermissionCode::fromString('organization.update'), IdentityId::fromString($identityId), $now);
            self::fail('Expected the cross-organization role assignment to be rejected.');
        } catch (RoleNotFound) {
        }

        try {
            $repository->revokeFromRole(OrganizationId::fromString($organizationId), RoleId::fromString($roleId), PermissionCode::fromString('organization.update'), IdentityId::fromString($identityId), $now);
            self::fail('Expected the cross-organization permission revocation to be rejected.');
        } catch (RoleNotFound) {
        }

        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => $permissionId, 'revoked_at' => null]);
    }

    #[Test]
    public function it_rejects_unknown_permission_codes_for_assignment_and_revocation(): void
    {
        $organizationId = $this->uuid();
        $identityId = $this->uuid();
        $roleId = $this->uuid();
        $now = new DateTimeImmutable('2026-08-07T00:00:00+00:00');
        $this->insertOrganization($organizationId, $identityId, $now);
        $this->insertRole($organizationId, $roleId, $now);
        $repository = new PgsqlPermissionAssignmentRepository;
        $organization = OrganizationId::fromString($organizationId);
        $role = RoleId::fromString($roleId);
        $identity = IdentityId::fromString($identityId);
        $unknownPermission = PermissionCode::fromString('organization.unknown');

        try {
            $repository->assignToRole($organization, $role, $unknownPermission, $identity, $now);
            self::fail('Expected an unknown permission assignment to be rejected.');
        } catch (PermissionNotFound) {
        }

        try {
            $repository->revokeFromRole($organization, $role, $unknownPermission, $identity, $now);
            self::fail('Expected an unknown permission revocation to be rejected.');
        } catch (PermissionNotFound) {
        }

        self::assertSame(0, DB::table('role_permissions')->count());
    }

    #[Test]
    public function it_records_permission_revocation_and_restores_the_permission_on_regrant(): void
    {
        $organizationId = $this->uuid();
        $identityId = $this->uuid();
        $roleId = $this->uuid();
        $permissionId = $this->uuid();
        $revokedBy = $this->uuid();
        $now = new DateTimeImmutable('2026-08-07T00:00:00+00:00');
        $revokedAt = new DateTimeImmutable('2026-08-07T01:00:00+00:00');
        $this->insertOrganization($organizationId, $identityId, $now);
        $this->insertRole($organizationId, $roleId, $now);
        $this->insertPermission($permissionId, $now);
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        $repository = new PgsqlPermissionAssignmentRepository;
        $organization = OrganizationId::fromString($organizationId);
        $role = RoleId::fromString($roleId);
        $permission = PermissionCode::fromString('organization.update');

        $repository->assignToRole($organization, $role, $permission, IdentityId::fromString($identityId), $now);
        $repository->revokeFromRole($organization, $role, $permission, IdentityId::fromString($revokedBy), $revokedAt);

        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => $permissionId, 'revoked_at' => $revokedAt, 'revoked_by_identity_id' => $revokedBy]);
        self::assertSame([], $repository->permissionsForIdentity($organization, IdentityId::fromString($identityId), $revokedAt));

        $repository->assignToRole($organization, $role, $permission, IdentityId::fromString($identityId), $revokedAt);

        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => $permissionId, 'revoked_at' => null, 'revoked_by_identity_id' => null]);
    }

    private function insertOrganization(string $organizationId, string $identityId, DateTimeImmutable $now): void
    {
        DB::table('organizations')->insert(['id' => $organizationId, 'name' => 'Organization', 'slug' => 'organization-'.$organizationId, 'created_by_identity_id' => $identityId, 'status' => 'active', 'version' => 1, 'timezone' => 'UTC', 'created_at' => $now, 'updated_at' => $now]);
    }

    private function insertRole(string $organizationId, string $roleId, DateTimeImmutable $now): void
    {
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => 'operator-'.$roleId, 'created_at' => $now, 'updated_at' => $now]);
    }

    private function insertPermission(string $permissionId, DateTimeImmutable $now): void
    {
        DB::table('permissions')->insert(['id' => $permissionId, 'code' => 'organization.update', 'capability' => 'organization', 'action' => 'update', 'description' => 'Update organization profiles.', 'risk_level' => 'medium', 'created_at' => $now]);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
