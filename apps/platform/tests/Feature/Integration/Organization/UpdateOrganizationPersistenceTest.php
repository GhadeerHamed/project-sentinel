<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Organization;

use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlPermissionAssignmentRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
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

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
