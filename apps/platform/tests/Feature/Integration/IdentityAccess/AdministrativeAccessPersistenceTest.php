<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\IdentityAccess;

use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlAdministrativeAccessRepository;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Tests\TestCase;

final class AdministrativeAccessPersistenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_recognizes_an_active_human_member_with_an_effective_administrative_permission(): void
    {
        $identityId = $this->identity('active');
        $organizationId = $this->organization($identityId);
        $this->membership($organizationId, $identityId, 'active');
        $this->assignment($organizationId, $identityId);

        self::assertTrue($this->hasAdministrativeAccess($organizationId));
    }

    #[Test]
    public function it_does_not_treat_a_role_name_as_an_administrative_capability(): void
    {
        $identityId = $this->identity('active');
        $organizationId = $this->organization($identityId);
        $this->membership($organizationId, $identityId, 'active');
        $this->assignment($organizationId, $identityId, roleKey: 'organization_owner', grantPermission: false);

        self::assertFalse($this->hasAdministrativeAccess($organizationId));
    }

    #[Test]
    public function it_does_not_count_an_inactive_membership(): void
    {
        $identityId = $this->identity('active');
        $organizationId = $this->organization($identityId);
        $this->membership($organizationId, $identityId, 'suspended');
        $this->assignment($organizationId, $identityId);

        self::assertFalse($this->hasAdministrativeAccess($organizationId));
    }

    #[Test]
    public function it_does_not_count_a_revoked_role_assignment(): void
    {
        $identityId = $this->identity('active');
        $organizationId = $this->organization($identityId);
        $this->membership($organizationId, $identityId, 'active');
        $this->assignment($organizationId, $identityId, revoked: true);

        self::assertFalse($this->hasAdministrativeAccess($organizationId));
    }

    #[Test]
    public function it_does_not_count_a_revoked_administrative_permission_grant(): void
    {
        $identityId = $this->identity('active');
        $organizationId = $this->organization($identityId);
        $this->membership($organizationId, $identityId, 'active');
        $this->assignment($organizationId, $identityId, grantRevoked: true);

        self::assertFalse($this->hasAdministrativeAccess($organizationId));
    }

    #[Test]
    public function it_does_not_count_a_permission_assignment_from_another_organization(): void
    {
        $identityId = $this->identity('active');
        $organizationId = $this->organization($identityId);
        $otherOrganizationId = $this->organization($identityId);
        $this->membership($organizationId, $identityId, 'active');
        $this->membership($otherOrganizationId, $identityId, 'active');
        $this->assignment($otherOrganizationId, $identityId);

        self::assertFalse($this->hasAdministrativeAccess($organizationId));
    }

    private function identity(string $status): string
    {
        $id = $this->uuid();
        DB::table('identity_users')->insert([
            'identity_id' => $id,
            'email' => $id.'@example.test',
            'display_name' => 'Identity',
            'status' => $status,
            'version' => 1,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        return $id;
    }

    private function organization(string $createdByIdentityId): string
    {
        $id = $this->uuid();
        DB::table('organizations')->insert([
            'id' => $id,
            'name' => 'Organization',
            'slug' => 'organization-'.$id,
            'created_by_identity_id' => $createdByIdentityId,
            'status' => 'active',
            'version' => 1,
            'timezone' => 'UTC',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        return $id;
    }

    private function membership(string $organizationId, string $identityId, string $status): void
    {
        DB::table('memberships')->insert([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'user_identity_id' => $identityId,
            'status' => $status,
            'activated_at' => $status === 'active' ? $this->now() : null,
            'suspended_at' => $status === 'suspended' ? $this->now() : null,
            'removed_at' => null,
            'version' => 1,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    private function assignment(string $organizationId, string $identityId, string $roleKey = 'operator', bool $grantPermission = true, bool $revoked = false, bool $grantRevoked = false): void
    {
        $roleId = $this->uuid();
        $permissionId = $this->permission();
        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => $roleKey.'-'.$roleId,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
        DB::table('role_assignments')->insert([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'identity_id' => $identityId,
            'role_id' => $roleId,
            'assigned_by_identity_id' => $identityId,
            'assigned_at' => $this->now(),
            'revoked_at' => $revoked ? $this->now() : null,
            'revoked_by_identity_id' => $revoked ? $identityId : null,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        if ($grantPermission) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'granted_at' => $this->now(),
                'granted_by_identity_id' => $identityId,
                'revoked_at' => $grantRevoked ? $this->now() : null,
                'revoked_by_identity_id' => $grantRevoked ? $identityId : null,
            ]);
        }
    }

    private function permission(): string
    {
        $permissionId = DB::table('permissions')->where('code', CorePermissions::manageOrganizationMembers()->value())->value('id');

        if (is_string($permissionId)) {
            return $permissionId;
        }

        $permissionId = $this->uuid();
        DB::table('permissions')->insert([
            'id' => $permissionId,
            'code' => CorePermissions::manageOrganizationMembers()->value(),
            'capability' => 'organization',
            'action' => 'member_manage',
            'description' => 'Manage organization members.',
            'risk_level' => 'high',
            'created_at' => $this->now(),
        ]);

        return $permissionId;
    }

    private function hasAdministrativeAccess(string $organizationId): bool
    {
        return (new PgsqlAdministrativeAccessRepository)->hasActiveHumanIdentityWithEffectivePermission(
            OrganizationId::fromString($organizationId),
            CorePermissions::manageOrganizationMembers(),
            $this->now(),
        );
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09T12:00:00+00:00');
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
