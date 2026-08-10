<?php

declare(strict_types=1);

namespace Tests\Feature\Event;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class CreateEventSourceEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_owner_issues_an_active_api_client_with_a_one_time_credential_and_secret_free_audits(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = $this->environment($organizationId, $applicationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEventSourceCreate($organizationId, $user->identity_id, 'organization_owner');

        $response = $this->postIssue($user, $organizationId, $projectId, $applicationId, $environmentId, ['name' => 'Checkout API'], 'issue-checkout-api');

        $response->assertCreated()
            ->assertJsonPath('data.type', 'event_source')
            ->assertJsonPath('data.attributes.project_id', $projectId)
            ->assertJsonPath('data.attributes.application_id', $applicationId)
            ->assertJsonPath('data.attributes.environment_id', $environmentId)
            ->assertJsonPath('data.attributes.source_type', 'api_client')
            ->assertJsonPath('data.attributes.name', 'Checkout API')
            ->assertJsonPath('data.attributes.status', 'active')
            ->assertJsonPath('data.attributes.version', 1)
            ->assertJsonPath('meta.issued_credential.shown_once', true);

        $eventSourceId = $response->json('data.id');
        $credentialId = $response->json('meta.issued_credential.id');
        $bearerCredential = $response->json('meta.issued_credential.bearer_token');
        self::assertIsString($eventSourceId);
        self::assertIsString($credentialId);
        self::assertIsString($bearerCredential);
        self::assertMatchesRegularExpression('/\Aesc_[0-9a-f-]{36}\.[0-9a-f]{64}\z/', $bearerCredential);

        $this->assertDatabaseHas('event_sources', [
            'id' => $eventSourceId,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'application_id' => $applicationId,
            'environment_id' => $environmentId,
            'source_type' => 'api_client',
            'name' => 'Checkout API',
            'status' => 'active',
            'version' => 1,
        ]);
        $secretHash = DB::table('event_source_credentials')->where('id', $credentialId)->value('secret_hash');
        self::assertIsString($secretHash);
        self::assertNotSame($bearerCredential, $secretHash);
        self::assertStringNotContainsString(substr($bearerCredential, strrpos($bearerCredential, '.') + 1), $secretHash);
        $this->assertDatabaseHas('audit_records', ['action' => 'event_source.create', 'resource_id' => $eventSourceId]);
        $this->assertDatabaseHas('audit_records', ['action' => 'event_source.credential.issue', 'resource_id' => $credentialId]);
        $audits = json_encode(DB::table('audit_records')->get(), JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString($bearerCredential, $audits);
        self::assertStringNotContainsString(substr($bearerCredential, strrpos($bearerCredential, '.') + 1), $audits);
        $this->assertDatabaseCount('outbox_messages', 0);
    }

    #[Test]
    public function an_actor_with_the_effective_event_source_create_permission_can_issue_a_source(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = $this->environment($organizationId, $applicationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEventSourceCreate($organizationId, $user->identity_id, 'event-source-issuer');

        $this->postIssue($user, $organizationId, $projectId, $applicationId, $environmentId, ['name' => 'Granted Issuer'], 'granted-event-source-create')
            ->assertCreated();
    }

    #[Test]
    public function an_active_member_without_event_source_create_permission_cannot_issue_a_source(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = $this->environment($organizationId, $applicationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);

        $this->postIssue($user, $organizationId, $projectId, $applicationId, $environmentId, ['name' => 'Denied'], 'denied-event-source-create')
            ->assertForbidden();
        $this->assertDatabaseCount('event_sources', 0);
    }

    #[Test]
    public function an_event_source_bearer_credential_cannot_authorize_issuance_without_a_human_session(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = $this->environment($organizationId, $applicationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEventSourceCreate($organizationId, $user->identity_id, 'event-source-issuer');
        $bearerCredential = $this->postIssue($user, $organizationId, $projectId, $applicationId, $environmentId, ['name' => 'Existing Source'], 'existing-event-source-create')
            ->assertCreated()
            ->json('meta.issued_credential.bearer_token');
        self::assertIsString($bearerCredential);

        $this->app->forgetScopedInstances();
        $this->app['auth']->forgetGuards();

        $this->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withHeader('Authorization', 'Bearer '.$bearerCredential)
            ->withHeader('Idempotency-Key', 'unauthenticated-event-source-create')
            ->postJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications/'.$applicationId.'/environments/'.$environmentId.'/event-sources', ['name' => 'Denied'])
            ->assertUnauthorized();
    }

    #[Test]
    public function missing_and_cross_parent_hierarchy_references_are_tenant_safely_not_found(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = $this->environment($organizationId, $applicationId, $user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $foreignProjectId = $this->project($otherOrganizationId, $user->identity_id);
        $otherProjectId = $this->project($organizationId, $user->identity_id);
        $foreignApplicationId = $this->application($organizationId, $otherProjectId, $user->identity_id);
        $otherApplicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $foreignEnvironmentId = $this->environment($organizationId, $otherApplicationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEventSourceCreate($organizationId, $user->identity_id, 'event-source-issuer');

        $this->postIssue($user, $organizationId, $this->uuid(), $applicationId, $environmentId, ['name' => 'Missing Project'], 'missing-event-source-project')
            ->assertNotFound()->assertJsonPath('error.code', 'project.not_found');
        $this->postIssue($user, $organizationId, $foreignProjectId, $applicationId, $environmentId, ['name' => 'Foreign Project'], 'foreign-event-source-project')
            ->assertNotFound()->assertJsonPath('error.code', 'project.not_found');
        $this->postIssue($user, $organizationId, $projectId, $foreignApplicationId, $environmentId, ['name' => 'Foreign Application'], 'foreign-event-source-application')
            ->assertNotFound()->assertJsonPath('error.code', 'application.not_found');
        $this->postIssue($user, $organizationId, $projectId, $applicationId, $foreignEnvironmentId, ['name' => 'Foreign Environment'], 'foreign-event-source-environment')
            ->assertNotFound()->assertJsonPath('error.code', 'environment.not_found');
    }

    #[Test]
    public function archived_parents_reject_issuance(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $archivedProjectId = $this->project($organizationId, $user->identity_id, 'archived');
        $projectApplicationId = $this->application($organizationId, $archivedProjectId, $user->identity_id);
        $projectEnvironmentId = $this->environment($organizationId, $projectApplicationId, $user->identity_id);
        $activeProjectId = $this->project($organizationId, $user->identity_id);
        $archivedApplicationId = $this->application($organizationId, $activeProjectId, $user->identity_id, 'archived');
        $applicationEnvironmentId = $this->environment($organizationId, $archivedApplicationId, $user->identity_id);
        $activeApplicationId = $this->application($organizationId, $activeProjectId, $user->identity_id);
        $archivedEnvironmentId = $this->environment($organizationId, $activeApplicationId, $user->identity_id, 'archived');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEventSourceCreate($organizationId, $user->identity_id, 'event-source-issuer');

        $this->postIssue($user, $organizationId, $archivedProjectId, $projectApplicationId, $projectEnvironmentId, ['name' => 'Archived Project'], 'archived-event-source-project')
            ->assertUnprocessable()->assertJsonPath('error.code', 'request.invalid_state');
        $this->postIssue($user, $organizationId, $activeProjectId, $archivedApplicationId, $applicationEnvironmentId, ['name' => 'Archived Application'], 'archived-event-source-application')
            ->assertUnprocessable()->assertJsonPath('error.code', 'request.invalid_state');
        $this->postIssue($user, $organizationId, $activeProjectId, $activeApplicationId, $archivedEnvironmentId, ['name' => 'Archived Environment'], 'archived-event-source-environment')
            ->assertUnprocessable()->assertJsonPath('error.code', 'request.invalid_state');
    }

    #[Test]
    public function only_name_is_client_controlled(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = $this->environment($organizationId, $applicationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEventSourceCreate($organizationId, $user->identity_id, 'event-source-issuer');

        $this->postIssue($user, $organizationId, $projectId, $applicationId, $environmentId, [
            'name' => 'Invalid Client Fields',
            'id' => $this->uuid(),
            'organization_id' => $this->uuid(),
            'project_id' => $this->uuid(),
            'application_id' => $this->uuid(),
            'environment_id' => $this->uuid(),
            'source_type' => 'agent',
            'status' => 'inactive',
            'credential_id' => $this->uuid(),
            'bearer_token' => 'secret',
            'created_at' => '2026-08-10T12:00:00Z',
        ], 'server-owned-event-source-fields')->assertUnprocessable()
            ->assertJsonPath('error.details.fields.id.0', 'The field is not supported.');
    }

    #[Test]
    public function idempotency_replays_the_source_without_revealing_the_credential_and_rejects_changed_input(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = $this->environment($organizationId, $applicationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEventSourceCreate($organizationId, $user->identity_id, 'event-source-issuer');

        $first = $this->postIssue($user, $organizationId, $projectId, $applicationId, $environmentId, ['name' => 'Idempotent Source'], 'idempotent-event-source-create')->assertCreated();
        $second = $this->postIssue($user, $organizationId, $projectId, $applicationId, $environmentId, ['name' => 'Idempotent Source'], 'idempotent-event-source-create')->assertCreated();

        self::assertSame($first->json('data.id'), $second->json('data.id'));
        $second->assertJsonMissingPath('meta.issued_credential');
        $this->assertDatabaseCount('event_sources', 1);
        $this->assertDatabaseCount('event_source_credentials', 1);
        $this->assertDatabaseCount('idempotency_records', 1);
        $this->assertDatabaseCount('audit_records', 2);

        $this->postIssue($user, $organizationId, $projectId, $applicationId, $environmentId, ['name' => 'Changed Source'], 'idempotent-event-source-create')
            ->assertConflict()
            ->assertJsonPath('error.code', 'idempotency.key_conflict');
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Event Source Issuer',
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
            'name' => 'Event Source Organization',
            'slug' => 'event-source-organization-'.$organizationId,
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
            'name' => 'Event Source Project',
            'slug' => 'event-source-project-'.$projectId,
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
            'name' => 'Event Source Application',
            'slug' => 'event-source-application-'.$applicationId,
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

    private function environment(string $organizationId, string $applicationId, string $identityId, string $status = 'active'): string
    {
        $environmentId = $this->uuid();
        $now = now();
        EnvironmentRecord::query()->create([
            'id' => $environmentId,
            'organization_id' => $organizationId,
            'application_id' => $applicationId,
            'name' => 'Event Source Environment',
            'slug' => 'event-source-environment-'.$environmentId,
            'environment_type' => 'production',
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => $status === 'archived' ? $now : null,
            'version' => $status === 'archived' ? 2 : 1,
        ]);

        return $environmentId;
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

    private function grantEventSourceCreate(string $organizationId, string $identityId, string $roleKey): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'event_source.create')->value('id');
        self::assertIsString($permissionId);
        $resolvedRoleKey = $roleKey === 'organization_owner' ? $roleKey : $roleKey.'-'.$roleId;
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => $resolvedRoleKey, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);
    }

    /** @param array<string, mixed> $payload */
    private function postIssue(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, string $applicationId, string $environmentId, array $payload, string $idempotencyKey): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)
            ->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications/'.$applicationId.'/environments/'.$environmentId.'/event-sources', $payload);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
