<?php

declare(strict_types=1);

namespace Tests\Feature\Project;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class CreateProjectEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_creates_a_project_with_canonical_server_owned_fields_and_side_effects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectCreate($organizationId, $user->identity_id);

        $response = $this->createProject($user, $organizationId, [
            'name' => 'Commerce Platform',
            'slug' => 'commerce-platform',
            'description' => 'Customer-facing commerce services.',
        ], 'commerce-project-creation');

        $response->assertCreated()
            ->assertJsonPath('data.type', 'project')
            ->assertJsonPath('data.attributes.name', 'Commerce Platform')
            ->assertJsonPath('data.attributes.slug', 'commerce-platform')
            ->assertJsonPath('data.attributes.description', 'Customer-facing commerce services.')
            ->assertJsonPath('data.attributes.status', 'active')
            ->assertJsonPath('data.attributes.version', 1)
            ->assertJsonPath('meta.correlation_id', $response->json('meta.correlation_id'));

        $projectId = $response->json('data.id');
        self::assertIsString($projectId);
        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => 'Commerce Platform',
            'slug' => 'commerce-platform',
            'description' => 'Customer-facing commerce services.',
            'status' => 'active',
            'created_by_identity_id' => $user->identity_id,
            'version' => 1,
            'archived_at' => null,
        ]);
        $this->assertDatabaseHas('outbox_messages', [
            'organization_id' => $organizationId,
            'event_type' => 'project.created',
            'aggregate_type' => 'project',
            'aggregate_id' => $projectId,
        ]);
        $this->assertDatabaseHas('audit_records', [
            'organization_id' => $organizationId,
            'action' => 'project.create',
            'resource_type' => 'project',
            'resource_id' => $projectId,
        ]);
    }

    #[Test]
    public function tenant_access_without_project_create_permission_cannot_create_a_project(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);

        $this->createProject($user, $organizationId, [
            'name' => 'Commerce Platform',
            'slug' => 'commerce-platform',
        ], 'unauthorized-project-creation')->assertForbidden();

        $this->assertDatabaseCount('projects', 0);
    }

    #[Test]
    public function a_project_cannot_be_created_through_an_organization_route_without_membership(): void
    {
        $user = $this->user();
        $authorizedOrganizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $this->membership($authorizedOrganizationId, $user->identity_id);
        $this->grantProjectCreate($authorizedOrganizationId, $user->identity_id);

        $this->createProject($user, $otherOrganizationId, [
            'name' => 'Other Organization Project',
            'slug' => 'other-organization-project',
        ], 'cross-organization-project')->assertNotFound();

        $this->assertDatabaseCount('projects', 0);
    }

    #[Test]
    public function server_owned_project_fields_are_rejected_from_the_request_body(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectCreate($organizationId, $user->identity_id);

        $this->createProject($user, $organizationId, [
            'name' => 'Commerce Platform',
            'slug' => 'commerce-platform',
            'organization_id' => $this->uuid(),
            'status' => 'archived',
            'version' => 99,
            'created_by_identity_id' => $this->uuid(),
            'created_at' => '2020-01-01T00:00:00Z',
        ], 'server-owned-fields')->assertUnprocessable()
            ->assertJsonPath('error.details.fields.organization_id.0', 'The field is not supported.');

        $this->assertDatabaseCount('projects', 0);
    }

    #[Test]
    public function project_slugs_are_unique_per_organization_but_may_be_reused_by_another_organization(): void
    {
        $user = $this->user();
        $firstOrganizationId = $this->organization($user->identity_id);
        $secondOrganizationId = $this->organization($user->identity_id);
        $this->membership($firstOrganizationId, $user->identity_id);
        $this->membership($secondOrganizationId, $user->identity_id);
        $this->grantProjectCreate($firstOrganizationId, $user->identity_id);
        $this->grantProjectCreate($secondOrganizationId, $user->identity_id);

        $this->createProject($user, $firstOrganizationId, [
            'name' => 'Commerce Platform',
            'slug' => 'commerce-platform',
        ], 'first-commerce-project')->assertCreated();
        $this->createProject($user, $firstOrganizationId, [
            'name' => 'Another Commerce Platform',
            'slug' => 'commerce-platform',
        ], 'duplicate-commerce-project')->assertConflict()
            ->assertJsonPath('error.code', 'project.slug_conflict');
        $this->createProject($user, $secondOrganizationId, [
            'name' => 'Another Commerce Platform',
            'slug' => 'commerce-platform',
        ], 'second-organization-commerce-project')->assertCreated();

        $this->assertDatabaseCount('projects', 2);
    }

    #[Test]
    public function an_identical_idempotent_retry_returns_the_original_project_without_repeating_side_effects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectCreate($organizationId, $user->identity_id);
        $payload = [
            'name' => 'Commerce Platform',
            'slug' => 'commerce-platform',
            'description' => 'Customer-facing commerce services.',
        ];

        $first = $this->createProject($user, $organizationId, $payload, 'same-request-key')->assertCreated();
        $second = $this->createProject($user, $organizationId, $payload, 'same-request-key')->assertCreated();

        self::assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseCount('idempotency_records', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->assertDatabaseCount('audit_records', 1);
    }

    #[Test]
    public function an_idempotency_key_cannot_be_reused_with_a_different_normalized_request(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectCreate($organizationId, $user->identity_id);

        $this->createProject($user, $organizationId, [
            'name' => 'Commerce Platform',
            'slug' => 'commerce-platform',
        ], 'conflicting-key')->assertCreated();
        $this->createProject($user, $organizationId, [
            'name' => 'Payments Platform',
            'slug' => 'payments-platform',
        ], 'conflicting-key')->assertConflict()
            ->assertJsonPath('error.code', 'idempotency.key_conflict');

        $this->assertDatabaseCount('projects', 1);
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Project Creator',
            'status' => 'active',
            'version' => 1,
        ]);
        $user->save();

        return $user;
    }

    private function organization(string $identityId): string
    {
        $organizationId = $this->uuid();
        OrganizationRecord::query()->create([
            'id' => $organizationId,
            'name' => 'Project Organization',
            'slug' => 'project-organization-'.$organizationId,
            'timezone' => 'UTC',
            'created_by_identity_id' => $identityId,
            'status' => 'active',
            'version' => 1,
        ]);

        return $organizationId;
    }

    private function membership(string $organizationId, string $identityId): void
    {
        $now = now();
        MembershipRecord::query()->create([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'user_identity_id' => $identityId,
            'status' => 'active',
            'activated_at' => $now,
            'version' => 1,
        ]);
    }

    private function grantProjectCreate(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'project.create')->value('id');
        self::assertIsString($permissionId);

        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'project-creator-'.$roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('role_assignments')->insert([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'identity_id' => $identityId,
            'role_id' => $roleId,
            'assigned_by_identity_id' => $identityId,
            'assigned_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'granted_at' => $now,
            'granted_by_identity_id' => $identityId,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function createProject(IdentityUserAuthenticatable $user, string $organizationId, array $payload, string $idempotencyKey): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)
            ->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/organizations/'.$organizationId.'/projects', $payload);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
