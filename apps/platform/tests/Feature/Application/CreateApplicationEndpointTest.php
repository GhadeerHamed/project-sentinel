<?php

declare(strict_types=1);

namespace Tests\Feature\Application;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
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

final class CreateApplicationEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_creates_an_application_with_canonical_server_owned_fields_and_side_effects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);

        $response = $this->postCreateApplication($user, $organizationId, $projectId, [
            'name' => 'Checkout API',
            'slug' => 'checkout-api',
            'description' => 'Processes checkout requests.',
            'criticality' => 'high',
        ], 'checkout-application-creation');

        $response->assertCreated()
            ->assertJsonPath('data.type', 'application')
            ->assertJsonPath('data.attributes.project_id', $projectId)
            ->assertJsonPath('data.attributes.name', 'Checkout API')
            ->assertJsonPath('data.attributes.slug', 'checkout-api')
            ->assertJsonPath('data.attributes.description', 'Processes checkout requests.')
            ->assertJsonPath('data.attributes.criticality', 'high')
            ->assertJsonPath('data.attributes.status', 'active')
            ->assertJsonPath('data.attributes.version', 1)
            ->assertJsonPath('meta.correlation_id', $response->json('meta.correlation_id'));

        $applicationId = $response->json('data.id');
        self::assertIsString($applicationId);
        $this->assertDatabaseHas('applications', [
            'id' => $applicationId,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'name' => 'Checkout API',
            'slug' => 'checkout-api',
            'description' => 'Processes checkout requests.',
            'criticality' => 'high',
            'status' => 'active',
            'created_by_identity_id' => $user->identity_id,
            'version' => 1,
            'archived_at' => null,
        ]);
        $this->assertDatabaseHas('outbox_messages', [
            'organization_id' => $organizationId,
            'event_type' => 'application.created',
            'aggregate_type' => 'application',
            'aggregate_id' => $applicationId,
        ]);
        $this->assertDatabaseHas('audit_records', [
            'organization_id' => $organizationId,
            'action' => 'application.create',
            'resource_type' => 'application',
            'resource_id' => $applicationId,
        ]);
    }

    #[Test]
    public function tenant_access_without_application_create_permission_cannot_create_an_application(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);

        $this->postCreateApplication($user, $organizationId, $projectId, $this->payload(), 'unauthorized-application-creation')
            ->assertForbidden();

        $this->assertDatabaseCount('applications', 0);
    }

    #[Test]
    public function an_unauthenticated_request_cannot_create_an_application(): void
    {
        $organizationId = $this->uuid();
        $projectId = $this->uuid();

        $this->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withHeader('Idempotency-Key', 'unauthenticated-application-creation')
            ->postJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications', $this->payload())
            ->assertUnauthorized();
    }

    #[Test]
    public function a_project_from_another_organization_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $foreignProjectId = $this->project($otherOrganizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);

        $this->postCreateApplication($user, $organizationId, $foreignProjectId, $this->payload(), 'foreign-project-application')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'project.not_found');

        $this->assertDatabaseCount('applications', 0);
    }

    #[Test]
    public function a_missing_project_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);

        $this->postCreateApplication($user, $organizationId, $this->uuid(), $this->payload(), 'missing-project-application')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'project.not_found');

        $this->assertDatabaseCount('applications', 0);
    }

    #[Test]
    public function an_archived_project_cannot_receive_a_new_application(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'archived');
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);

        $this->postCreateApplication($user, $organizationId, $projectId, $this->payload(), 'archived-project-application')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'request.invalid_state');

        $this->assertDatabaseCount('applications', 0);
    }

    #[Test]
    #[DataProvider('criticalities')]
    public function each_canonical_criticality_can_be_created(string $criticality): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);

        $this->postCreateApplication($user, $organizationId, $projectId, [
            'name' => ucfirst($criticality).' Application',
            'slug' => $criticality.'-application',
            'criticality' => $criticality,
        ], 'criticality-'.$criticality)->assertCreated()
            ->assertJsonPath('data.attributes.criticality', $criticality);
    }

    #[Test]
    public function an_invalid_criticality_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);

        $this->postCreateApplication($user, $organizationId, $projectId, [...$this->payload(), 'criticality' => 'mission-critical'], 'invalid-criticality')
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.criticality.0', 'The selected criticality is invalid.');

        $this->assertDatabaseCount('applications', 0);
    }

    #[Test]
    public function server_owned_application_fields_are_rejected_from_the_request_body(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);

        $this->postCreateApplication($user, $organizationId, $projectId, [
            ...$this->payload(),
            'organization_id' => $this->uuid(),
            'project_id' => $this->uuid(),
            'status' => 'archived',
            'version' => 99,
            'created_by_identity_id' => $this->uuid(),
            'created_at' => '2020-01-01T00:00:00Z',
            'archived_at' => '2020-01-01T00:00:00Z',
        ], 'server-owned-application-fields')->assertUnprocessable()
            ->assertJsonPath('error.details.fields.organization_id.0', 'The field is not supported.');

        $this->assertDatabaseCount('applications', 0);
    }

    #[Test]
    public function application_slugs_are_unique_per_project_but_may_be_reused_by_another_project(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $firstProjectId = $this->project($organizationId, $user->identity_id);
        $secondProjectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);

        $this->postCreateApplication($user, $organizationId, $firstProjectId, $this->payload(), 'first-checkout-application')->assertCreated();
        $this->postCreateApplication($user, $organizationId, $firstProjectId, [...$this->payload(), 'name' => 'Checkout Worker'], 'duplicate-checkout-application')
            ->assertConflict()
            ->assertJsonPath('error.code', 'application.slug_conflict');
        $this->postCreateApplication($user, $organizationId, $secondProjectId, [...$this->payload(), 'name' => 'Checkout Worker'], 'second-project-checkout-application')
            ->assertCreated();

        $this->assertDatabaseCount('applications', 2);
    }

    #[Test]
    public function an_identical_idempotent_retry_returns_the_original_application_without_repeating_side_effects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);
        $payload = $this->payload();

        $first = $this->postCreateApplication($user, $organizationId, $projectId, $payload, 'same-application-request')->assertCreated();
        $second = $this->postCreateApplication($user, $organizationId, $projectId, $payload, 'same-application-request')->assertCreated();

        self::assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('applications', 1);
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
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);

        $this->postCreateApplication($user, $organizationId, $projectId, $this->payload(), 'conflicting-application-key')->assertCreated();
        $this->postCreateApplication($user, $organizationId, $projectId, [
            'name' => 'Payments API',
            'slug' => 'payments-api',
            'criticality' => 'critical',
        ], 'conflicting-application-key')->assertConflict()
            ->assertJsonPath('error.code', 'idempotency.key_conflict');

        $this->assertDatabaseCount('applications', 1);
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Application Creator',
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
            'name' => 'Application Organization',
            'slug' => 'application-organization-'.$organizationId,
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
            'name' => 'Application Project',
            'slug' => 'application-project-'.$projectId,
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

    private function grantApplicationCreate(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'application.create')->value('id');
        self::assertIsString($permissionId);

        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'application-creator-'.$roleId,
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

    /** @return array{name: string, slug: string, description: string, criticality: string} */
    private function payload(): array
    {
        return [
            'name' => 'Checkout API',
            'slug' => 'checkout-api',
            'description' => 'Processes checkout requests.',
            'criticality' => 'high',
        ];
    }

    /** @return array<string, array{string}> */
    public static function criticalities(): array
    {
        return [
            'critical' => ['critical'],
            'high' => ['high'],
            'medium' => ['medium'],
            'low' => ['low'],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function postCreateApplication(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, array $payload, string $idempotencyKey): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)
            ->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications', $payload);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
