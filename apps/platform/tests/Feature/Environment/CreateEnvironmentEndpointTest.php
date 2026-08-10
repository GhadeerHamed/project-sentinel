<?php

declare(strict_types=1);

namespace Tests\Feature\Environment;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class CreateEnvironmentEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_creates_an_environment_with_canonical_server_owned_fields_and_side_effects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $response = $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, $this->payload(), 'production-environment-create');

        $response->assertCreated()
            ->assertJsonPath('data.type', 'environment')
            ->assertJsonPath('data.attributes.application_id', $applicationId)
            ->assertJsonPath('data.attributes.name', 'Production')
            ->assertJsonPath('data.attributes.slug', 'production')
            ->assertJsonPath('data.attributes.environment_type', 'production')
            ->assertJsonPath('data.attributes.status', 'active')
            ->assertJsonPath('data.attributes.version', 1)
            ->assertJsonPath('meta.correlation_id', $response->json('meta.correlation_id'));

        $environmentId = $response->json('data.id');
        self::assertIsString($environmentId);
        $this->assertDatabaseHas('environments', [
            'id' => $environmentId,
            'organization_id' => $organizationId,
            'application_id' => $applicationId,
            'name' => 'Production',
            'slug' => 'production',
            'environment_type' => 'production',
            'status' => 'active',
            'created_by_identity_id' => $user->identity_id,
            'version' => 1,
            'archived_at' => null,
        ]);
        $this->assertDatabaseHas('outbox_messages', [
            'organization_id' => $organizationId,
            'event_type' => 'environment.created',
            'aggregate_type' => 'environment',
            'aggregate_id' => $environmentId,
        ]);
        $this->assertDatabaseHas('audit_records', [
            'organization_id' => $organizationId,
            'action' => 'environment.create',
            'resource_type' => 'environment',
            'resource_id' => $environmentId,
        ]);
    }

    #[Test]
    public function tenant_access_without_environment_create_permission_cannot_create_an_environment(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, $this->payload(), 'unauthorized-environment-create')
            ->assertForbidden();

        $this->assertDatabaseCount('environments', 0);
    }

    #[Test]
    public function an_unauthenticated_request_cannot_create_an_environment(): void
    {
        $this->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withHeader('Idempotency-Key', 'unauthenticated-environment-create')
            ->postJson('/api/v1/organizations/'.$this->uuid().'/projects/'.$this->uuid().'/applications/'.$this->uuid().'/environments', $this->payload())
            ->assertUnauthorized();
    }

    #[Test]
    public function a_missing_project_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $this->uuid(), $this->uuid(), $this->payload(), 'missing-environment-project')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'project.not_found');
    }

    #[Test]
    public function a_project_from_another_organization_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $foreignProjectId = $this->project($otherOrganizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $foreignProjectId, $this->uuid(), $this->payload(), 'foreign-environment-project')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'project.not_found');
    }

    #[Test]
    public function a_missing_application_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $this->uuid(), $this->payload(), 'missing-environment-application')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'application.not_found');
    }

    #[Test]
    public function an_application_belonging_to_another_project_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $otherProjectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $otherProjectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, $this->payload(), 'cross-project-environment-application')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'application.not_found');
    }

    #[Test]
    public function an_archived_project_cannot_receive_a_new_environment(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'archived');
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, $this->payload(), 'archived-environment-project')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'request.invalid_state');
    }

    #[Test]
    public function an_archived_application_cannot_receive_a_new_environment(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id, 'archived');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, $this->payload(), 'archived-environment-application')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'request.invalid_state');
    }

    #[Test]
    #[DataProvider('environmentTypes')]
    public function each_canonical_environment_type_can_be_created(string $environmentType): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, [
            'name' => ucfirst($environmentType),
            'slug' => $environmentType,
            'environment_type' => $environmentType,
        ], 'environment-type-'.$environmentType)->assertCreated()
            ->assertJsonPath('data.attributes.environment_type', $environmentType);
    }

    #[Test]
    public function an_invalid_environment_type_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, [...$this->payload(), 'environment_type' => 'disaster_recovery'], 'invalid-environment-type')
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.environment_type.0', 'The selected environment type is invalid.');
    }

    #[Test]
    public function server_owned_environment_fields_are_rejected_from_the_request_body(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, [
            ...$this->payload(),
            'organization_id' => $this->uuid(),
            'project_id' => $this->uuid(),
            'application_id' => $this->uuid(),
            'status' => 'archived',
            'version' => 99,
            'created_by_identity_id' => $this->uuid(),
            'created_at' => '2020-01-01T00:00:00Z',
            'archived_at' => '2020-01-01T00:00:00Z',
        ], 'server-owned-environment-fields')->assertUnprocessable()
            ->assertJsonPath('error.details.fields.organization_id.0', 'The field is not supported.');
    }

    #[Test]
    public function environment_slugs_are_unique_per_application_but_may_be_reused_by_another_application(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $firstApplicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $secondApplicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $firstApplicationId, $this->payload(), 'first-production-environment')->assertCreated();
        $this->postCreateEnvironment($user, $organizationId, $projectId, $firstApplicationId, [...$this->payload(), 'name' => 'Primary'], 'duplicate-production-environment')
            ->assertConflict()
            ->assertJsonPath('error.code', 'environment.slug_conflict');
        $this->postCreateEnvironment($user, $organizationId, $projectId, $secondApplicationId, [...$this->payload(), 'name' => 'Primary'], 'second-application-production-environment')
            ->assertCreated();

        $this->assertDatabaseCount('environments', 2);
    }

    #[Test]
    public function an_identical_idempotent_retry_returns_the_original_environment_without_repeating_side_effects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $first = $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, $this->payload(), 'same-environment-request')->assertCreated();
        $second = $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, $this->payload(), 'same-environment-request')->assertCreated();

        self::assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('environments', 1);
        $this->assertDatabaseCount('idempotency_records', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->assertDatabaseCount('audit_records', 1);
    }

    #[Test]
    public function an_idempotency_key_cannot_be_reused_with_a_different_normalized_request(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentCreate($organizationId, $user->identity_id);

        $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, $this->payload(), 'conflicting-environment-key')->assertCreated();
        $this->postCreateEnvironment($user, $organizationId, $projectId, $applicationId, [
            'name' => 'Staging',
            'slug' => 'staging',
            'environment_type' => 'staging',
        ], 'conflicting-environment-key')->assertConflict()
            ->assertJsonPath('error.code', 'idempotency.key_conflict');

        $this->assertDatabaseCount('environments', 1);
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Environment Creator',
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
            'name' => 'Environment Organization',
            'slug' => 'environment-organization-'.$organizationId,
            'timezone' => 'UTC',
            'created_by_identity_id' => $identityId,
            'status' => 'active',
            'version' => 1,
        ]);

        return $organizationId;
    }

    private function project(string $organizationId, string $identityId, string $status = 'active'): string
    {
        $projectId = $this->uuid();
        $now = now();
        ProjectRecord::query()->create([
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => 'Environment Project',
            'slug' => 'environment-project-'.$projectId,
            'description' => null,
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => $status === 'archived' ? $now : null,
            'version' => $status === 'archived' ? 2 : 1,
        ]);

        return $projectId;
    }

    private function application(string $organizationId, string $projectId, string $identityId, string $status = 'active'): string
    {
        $applicationId = $this->uuid();
        $now = now();
        ApplicationRecord::query()->create([
            'id' => $applicationId,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'name' => 'Environment Application',
            'slug' => 'environment-application-'.$applicationId,
            'description' => null,
            'criticality' => 'high',
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => $status === 'archived' ? $now : null,
            'version' => $status === 'archived' ? 2 : 1,
        ]);

        return $applicationId;
    }

    private function membership(string $organizationId, string $identityId): void
    {
        MembershipRecord::query()->create([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'user_identity_id' => $identityId,
            'status' => 'active',
            'activated_at' => now(),
            'version' => 1,
        ]);
    }

    private function grantEnvironmentCreate(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'environment.create')->value('id');
        self::assertIsString($permissionId);

        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'environment-creator-'.$roleId,
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

    /** @return array{name: string, slug: string, environment_type: string} */
    private function payload(): array
    {
        return [
            'name' => 'Production',
            'slug' => 'production',
            'environment_type' => 'production',
        ];
    }

    /** @return array<string, array{string}> */
    public static function environmentTypes(): array
    {
        return [
            'production' => ['production'],
            'staging' => ['staging'],
            'testing' => ['testing'],
            'development' => ['development'],
            'sandbox' => ['sandbox'],
            'other' => ['other'],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function postCreateEnvironment(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, string $applicationId, array $payload, string $idempotencyKey): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)
            ->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications/'.$applicationId.'/environments', $payload);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
