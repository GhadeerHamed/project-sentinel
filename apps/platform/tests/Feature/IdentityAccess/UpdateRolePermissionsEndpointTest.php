<?php

declare(strict_types=1);

namespace Tests\Feature\IdentityAccess;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class UpdateRolePermissionsEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_can_grant_a_core_permission_and_records_the_operation(): void
    {
        [$user, $organizationId] = $this->administrator();
        $roleId = $this->role($organizationId);

        $this->update($user, $organizationId, $roleId, ['grant' => ['organization.update'], 'revoke' => []])->assertNoContent();

        $permissionId = $this->permissionId('organization.update');
        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => $permissionId, 'revoked_at' => null]);
        $this->assertDatabaseHas('outbox_messages', ['organization_id' => $organizationId, 'event_type' => 'role.permissions_updated']);
        $this->assertDatabaseHas('audit_records', ['organization_id' => $organizationId, 'action' => 'role.permissions.update', 'resource_id' => $roleId]);
    }

    #[Test]
    public function an_authorized_member_can_revoke_a_core_permission(): void
    {
        [$user, $organizationId] = $this->administrator();
        $roleId = $this->role($organizationId);
        $this->grantPermission($roleId, 'organization.update', $user->identity_id);

        $this->update($user, $organizationId, $roleId, ['grant' => [], 'revoke' => ['organization.update']])->assertNoContent();

        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => $this->permissionId('organization.update'), 'revoked_by_identity_id' => $user->identity_id]);
    }

    #[Test]
    public function a_revoked_permission_is_restored_when_it_is_granted_again(): void
    {
        [$user, $organizationId] = $this->administrator();
        $roleId = $this->role($organizationId);
        $this->grantPermission($roleId, 'organization.update', $user->identity_id, revoked: true);

        $this->update($user, $organizationId, $roleId, ['grant' => ['organization.update'], 'revoke' => []])->assertNoContent();

        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => $this->permissionId('organization.update'), 'revoked_at' => null, 'revoked_by_identity_id' => null]);
    }

    #[Test]
    public function unknown_permissions_are_rejected(): void
    {
        [$user, $organizationId] = $this->administrator();

        $this->update($user, $organizationId, $this->role($organizationId), ['grant' => ['organization.unknown'], 'revoke' => []])->assertUnprocessable();
    }

    #[Test]
    public function duplicate_permission_changes_are_rejected(): void
    {
        [$user, $organizationId] = $this->administrator();

        $this->update($user, $organizationId, $this->role($organizationId), ['grant' => ['organization.update', 'organization.update'], 'revoke' => []])->assertUnprocessable();
    }

    #[Test]
    public function overlapping_permission_changes_are_rejected(): void
    {
        [$user, $organizationId] = $this->administrator();

        $this->update($user, $organizationId, $this->role($organizationId), ['grant' => ['organization.update'], 'revoke' => ['organization.update']])->assertUnprocessable();
    }

    #[Test]
    public function an_empty_permission_change_is_rejected(): void
    {
        [$user, $organizationId] = $this->administrator();

        $this->update($user, $organizationId, $this->role($organizationId), ['grant' => [], 'revoke' => []])->assertUnprocessable();
    }

    #[Test]
    public function a_role_from_another_organization_is_not_available(): void
    {
        [$user, $organizationId] = $this->administrator();
        $otherOrganizationId = $this->organization($user->identity_id);
        $otherRoleId = $this->role($otherOrganizationId);

        $this->update($user, $organizationId, $otherRoleId, ['grant' => ['organization.update'], 'revoke' => []])->assertNotFound();

        self::assertSame(0, DB::table('role_permissions')->where('role_id', $otherRoleId)->count());
    }

    #[Test]
    public function an_unauthorized_member_cannot_update_role_permissions(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);

        $this->update($user, $organizationId, $this->role($organizationId), ['grant' => ['organization.update'], 'revoke' => []])->assertForbidden();
    }

    #[Test]
    public function revoking_member_management_succeeds_when_a_backup_administrator_remains(): void
    {
        [$user, $organizationId, $roleId] = $this->administrator();
        $backup = $this->user();
        $this->membership($organizationId, $backup->identity_id);
        $this->administratorRole($organizationId, $backup->identity_id);

        $this->update($user, $organizationId, $roleId, ['grant' => [], 'revoke' => ['organization.member.manage']])->assertNoContent();

        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => $this->permissionId('organization.member.manage'), 'revoked_by_identity_id' => $user->identity_id]);
    }

    #[Test]
    public function revoking_the_final_administrative_recovery_capability_is_rejected_and_rolled_back(): void
    {
        [$user, $organizationId, $roleId] = $this->administrator();

        $this->update($user, $organizationId, $roleId, ['grant' => [], 'revoke' => ['organization.member.manage']])->assertConflict();

        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleId, 'permission_id' => $this->permissionId('organization.member.manage'), 'revoked_at' => null]);
        $this->assertDatabaseMissing('outbox_messages', ['organization_id' => $organizationId, 'event_type' => 'role.permissions_updated']);
        $this->assertDatabaseMissing('audit_records', ['organization_id' => $organizationId, 'action' => 'role.permissions.update']);
    }

    /** @return array{IdentityUserAuthenticatable, string, string} */
    private function administrator(): array
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);

        return [$user, $organizationId, $this->administratorRole($organizationId, $user->identity_id)];
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill(['identity_id' => $this->uuid(), 'email' => $this->uuid().'@example.test', 'display_name' => 'User', 'status' => 'active', 'version' => 1]);
        $user->save();

        return $user;
    }

    private function organization(string $identityId): string
    {
        $id = $this->uuid();
        OrganizationRecord::query()->create(['id' => $id, 'name' => 'Acme', 'slug' => 'acme-'.$id, 'timezone' => 'UTC', 'created_by_identity_id' => $identityId, 'status' => 'active', 'version' => 1]);

        return $id;
    }

    private function membership(string $organizationId, string $identityId): void
    {
        MembershipRecord::query()->create(['id' => $this->uuid(), 'organization_id' => $organizationId, 'user_identity_id' => $identityId, 'status' => 'active', 'activated_at' => now(), 'version' => 1]);
    }

    private function administratorRole(string $organizationId, string $identityId): string
    {
        $roleId = $this->role($organizationId);
        $now = now();
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        $this->grantPermission($roleId, 'organization.member.manage', $identityId);

        return $roleId;
    }

    private function role(string $organizationId): string
    {
        $id = $this->uuid();
        $now = now();
        DB::table('organization_roles')->insert(['id' => $id, 'organization_id' => $organizationId, 'role_key' => 'operator-'.$id, 'created_at' => $now, 'updated_at' => $now]);

        return $id;
    }

    private function grantPermission(string $roleId, string $permissionCode, string $identityId, bool $revoked = false): void
    {
        $now = now();
        DB::table('role_permissions')->updateOrInsert(
            ['role_id' => $roleId, 'permission_id' => $this->permissionId($permissionCode)],
            [
                'granted_at' => $now,
                'granted_by_identity_id' => $identityId,
                'revoked_at' => $revoked ? $now : null,
                'revoked_by_identity_id' => $revoked ? $identityId : null,
            ],
        );
    }

    private function permissionId(string $permissionCode): string
    {
        $permissionId = DB::table('permissions')->where('code', $permissionCode)->value('id');

        if (! is_string($permissionId)) {
            throw new \RuntimeException('The Core permission could not be resolved.');
        }

        return $permissionId;
    }

    /** @param array{grant: list<string>, revoke: list<string>} $payload */
    private function update(IdentityUserAuthenticatable $user, string $organizationId, string $roleId, array $payload): TestResponse
    {
        return $this->actingAs($user)->withSession(['_token' => 'csrf-token'])->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->patchJson('/api/v1/organizations/'.$organizationId.'/roles/'.$roleId.'/permissions', $payload);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
