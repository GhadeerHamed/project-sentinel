<?php

declare(strict_types=1);

namespace Tests\Feature\Organization;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class UpdateOrganizationEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_active_member_with_the_explicit_permission_can_update_its_organization(): void
    {
        $user = $this->identityUser();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id, 'active');
        $this->grantPermission($organizationId, $user->identity_id);
        $response = $this->actingAs($user)->csrfProtectedJsonPatch($organizationId, ['name' => 'Acme Operations', 'timezone' => 'Europe/Bucharest'], '"1"');
        $response->assertOk()
            ->assertHeader('ETag', '"2"')
            ->assertJsonPath('data.name', 'Acme Operations')
            ->assertJsonPath('data.timezone', 'Europe/Bucharest')
            ->assertJsonPath('data.version', 2);
        $this->assertDatabaseHas('organizations', ['id' => $organizationId, 'name' => 'Acme Operations', 'timezone' => 'Europe/Bucharest', 'version' => 2]);
        $this->assertDatabaseHas('outbox_messages', ['organization_id' => $organizationId, 'event_type' => 'organization.updated', 'aggregate_version' => 2]);
        $this->assertDatabaseHas('audit_records', ['organization_id' => $organizationId, 'actor_id' => $user->identity_id, 'action' => 'organization.update']);
    }

    #[Test]
    public function membership_without_the_explicit_permission_is_denied(): void
    {
        $user = $this->identityUser();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id, 'active');
        $this->actingAs($user)->csrfProtectedJsonPatch($organizationId, ['name' => 'Denied'], '"1"')->assertForbidden();
    }

    #[Test]
    public function another_organizations_permission_and_suspended_membership_do_not_authorize_updates(): void
    {
        $user = $this->identityUser();
        $organizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id, 'active');
        $this->membership($otherOrganizationId, $user->identity_id, 'active');
        $this->grantPermission($otherOrganizationId, $user->identity_id);
        $this->actingAs($user)->csrfProtectedJsonPatch($organizationId, ['name' => 'Denied'], '"1"')->assertForbidden();
        $suspendedOrganizationId = $this->organization($user->identity_id);
        $this->membership($suspendedOrganizationId, $user->identity_id, 'suspended');
        $this->actingAs($user)->csrfProtectedJsonPatch($suspendedOrganizationId, ['name' => 'Hidden'], '"1"')->assertNotFound();
    }

    #[Test]
    public function a_stale_if_match_version_is_rejected(): void
    {
        $user = $this->identityUser();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id, 'active');
        $this->grantPermission($organizationId, $user->identity_id);
        $this->actingAs($user)->csrfProtectedJsonPatch($organizationId, ['name' => 'Stale'], '"2"')->assertStatus(412);
    }

    #[Test]
    public function a_supplied_null_profile_field_is_invalid_rather_than_an_empty_patch(): void
    {
        $user = $this->identityUser();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id, 'active');
        $this->grantPermission($organizationId, $user->identity_id);

        foreach (['name' => 'The name field must be a string.', 'timezone' => 'The timezone field must be a string.'] as $field => $message) {
            $this->actingAs($user)->csrfProtectedJsonPatch($organizationId, [$field => null], '"1"')
                ->assertUnprocessable()
                ->assertJsonPath('error.details.fields.'.$field.'.0', $message);
        }
    }

    #[Test]
    public function an_empty_patch_is_rejected(): void
    {
        $user = $this->identityUser();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id, 'active');
        $this->grantPermission($organizationId, $user->identity_id);

        $this->actingAs($user)->csrfProtectedJsonPatch($organizationId, [], '"1"')
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.name.0', 'At least one profile field is required.');
    }

    private function identityUser(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill(['identity_id' => $this->uuid(), 'email' => $this->uuid().'@example.test', 'display_name' => 'Organization User', 'status' => 'active', 'version' => 1]);
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
        $now = now();
        MembershipRecord::query()->create(['id' => $this->uuid(), 'organization_id' => $organizationId, 'user_identity_id' => $identityId, 'status' => $status, 'activated_at' => $status === 'active' ? $now : null, 'suspended_at' => $status === 'suspended' ? $now : null, 'version' => 1]);
    }

    private function grantPermission(string $organizationId, string $identityId): void
    {
        $now = now();
        $roleId = $this->uuid();
        $permissionId = $this->uuid();
        DB::table('permissions')->insert(['id' => $permissionId, 'code' => 'organization.update', 'capability' => 'organization', 'action' => 'update', 'description' => 'Update organization.', 'risk_level' => 'medium', 'created_at' => $now]);
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => 'operator-'.$roleId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);
    }

    private function csrfProtectedJsonPatch(string $organizationId, array $data, string $ifMatch): TestResponse
    {
        return $this->withSession(['_token' => 'csrf-token'])->withHeader('X-CSRF-TOKEN', 'csrf-token')->withHeader('If-Match', $ifMatch)->patchJson('/api/v1/organizations/'.$organizationId, $data);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
