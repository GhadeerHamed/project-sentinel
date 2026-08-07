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

final class InviteMembershipEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_active_member_with_permission_can_invite_an_inactive_membership(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $targetIdentityId = $this->uuid();
        $this->membership($organizationId, $user->identity_id, 'active');
        $this->grant($organizationId, $user->identity_id);

        $response = $this->actingAs($user)->csrfProtectedJsonPost($organizationId, ['user_identity_id' => $targetIdentityId]);

        $response->assertCreated()->assertJsonPath('data.status', 'invited');
        $membershipId = $response->json('data.membership_id');
        $this->assertDatabaseHas('memberships', ['id' => $membershipId, 'organization_id' => $organizationId, 'user_identity_id' => $targetIdentityId, 'status' => 'invited']);
        $this->assertDatabaseHas('outbox_messages', ['organization_id' => $organizationId, 'event_type' => 'membership.invited']);
        $this->assertDatabaseHas('audit_records', ['organization_id' => $organizationId, 'action' => 'membership.invite', 'resource_id' => $membershipId]);
    }

    #[Test]
    public function membership_without_permission_cannot_invite(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id, 'active');

        $this->actingAs($user)->csrfProtectedJsonPost($organizationId, ['user_identity_id' => $this->uuid()])->assertForbidden();
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

    private function membership(string $organizationId, string $identityId, string $status): void
    {
        MembershipRecord::query()->create(['id' => $this->uuid(), 'organization_id' => $organizationId, 'user_identity_id' => $identityId, 'status' => $status, 'activated_at' => $status === 'active' ? now() : null, 'version' => 1]);
    }

    private function grant(string $organizationId, string $identityId): void
    {
        $now = now();
        $roleId = $this->uuid();
        $permissionId = $this->uuid();
        DB::table('permissions')->insert(['id' => $permissionId, 'code' => 'organization.member.manage', 'capability' => 'organization', 'action' => 'member_manage', 'description' => 'Manage members.', 'risk_level' => 'medium', 'created_at' => $now]);
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => 'operator-'.$roleId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }

    /** @param array<string, string> $data */
    private function csrfProtectedJsonPost(string $organizationId, array $data): TestResponse
    {
        return $this->withSession(['_token' => 'csrf-token'])->withHeader('X-CSRF-TOKEN', 'csrf-token')->postJson('/api/v1/organizations/'.$organizationId.'/memberships', $data);
    }
}
