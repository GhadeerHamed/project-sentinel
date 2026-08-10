<?php

declare(strict_types=1);

namespace Tests\Feature\Environment;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class ListEnvironmentsEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_lists_only_active_environments_for_the_requested_application_in_canonical_order(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id, 'active', 'commerce-api');
        $otherApplicationId = $this->application($organizationId, $projectId, $user->identity_id, 'active', 'payments-api');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);
        $earlierEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f10';
        $laterEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f11';
        $otherEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f12';
        $this->environment($organizationId, $applicationId, $user->identity_id, $earlierEnvironmentId, 'Staging', 'staging', 'staging', 'active', '2026-08-10T10:00:00+00:00');
        $this->environment($organizationId, $applicationId, $user->identity_id, $laterEnvironmentId, 'Production', 'production', 'production', 'active', '2026-08-10T11:00:00+00:00');
        $this->environment($organizationId, $otherApplicationId, $user->identity_id, $otherEnvironmentId, 'Other Production', 'production', 'production', 'active', '2026-08-10T12:00:00+00:00');

        $response = $this->listEnvironments($user, $organizationId, $projectId, $applicationId);

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $laterEnvironmentId)
            ->assertJsonPath('data.1.id', $earlierEnvironmentId)
            ->assertJsonPath('data.0.attributes.application_id', $applicationId)
            ->assertJsonPath('data.0.attributes.environment_type', 'production')
            ->assertJsonMissing(['id' => $otherEnvironmentId])
            ->assertJsonPath('pagination.has_more', false)
            ->assertJsonPath('pagination.next_cursor', null);
        $firstEnvironment = $response->json('data.0');
        self::assertIsArray($firstEnvironment);
        self::assertSame(['id', 'type', 'attributes'], array_keys($firstEnvironment));
        $attributes = $firstEnvironment['attributes'] ?? null;
        self::assertIsArray($attributes);
        self::assertSame(
            ['application_id', 'name', 'slug', 'environment_type', 'status', 'version', 'created_at', 'updated_at'],
            array_keys($attributes),
        );
    }

    #[Test]
    public function the_environment_list_uses_an_opaque_cursor_with_stable_canonical_ordering_and_filter_scope(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id, 'active', 'commerce-api');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);
        $oldestEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f20';
        $middleEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f21';
        $newestEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f22';
        $this->environment($organizationId, $applicationId, $user->identity_id, $oldestEnvironmentId, 'Oldest', 'oldest', 'testing', 'active', '2026-08-10T10:00:00+00:00');
        $this->environment($organizationId, $applicationId, $user->identity_id, $middleEnvironmentId, 'Middle', 'middle', 'staging', 'active', '2026-08-10T11:00:00+00:00');
        $this->environment($organizationId, $applicationId, $user->identity_id, $newestEnvironmentId, 'Newest', 'newest', 'production', 'active', '2026-08-10T12:00:00+00:00');

        $firstPage = $this->listEnvironments($user, $organizationId, $projectId, $applicationId, ['limit' => '1'])->assertOk()
            ->assertJsonPath('data.0.id', $newestEnvironmentId)
            ->assertJsonPath('pagination.has_more', true);
        $nextCursor = $firstPage->json('pagination.next_cursor');
        self::assertIsString($nextCursor);

        $this->listEnvironments($user, $organizationId, $projectId, $applicationId, ['limit' => '1', 'cursor' => $nextCursor])->assertOk()
            ->assertJsonPath('data.0.id', $middleEnvironmentId)
            ->assertJsonPath('pagination.has_more', true);
        $this->listEnvironments($user, $organizationId, $projectId, $applicationId, ['status' => 'archived', 'cursor' => $nextCursor])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.cursor.0', 'The cursor is invalid.');
    }

    #[Test]
    public function archived_environments_are_excluded_by_default_and_available_with_the_historical_status_filter(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id, 'active', 'commerce-api');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);
        $activeEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f30';
        $archivedEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f31';
        $this->environment($organizationId, $applicationId, $user->identity_id, $activeEnvironmentId, 'Active', 'active', 'production', 'active', '2026-08-10T10:00:00+00:00');
        $this->environment($organizationId, $applicationId, $user->identity_id, $archivedEnvironmentId, 'Archived', 'archived', 'production', 'archived', '2026-08-10T11:00:00+00:00');

        $this->listEnvironments($user, $organizationId, $projectId, $applicationId)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeEnvironmentId);
        $this->listEnvironments($user, $organizationId, $projectId, $applicationId, ['status' => 'archived'])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $archivedEnvironmentId)
            ->assertJsonPath('data.0.attributes.status', 'archived');
    }

    #[Test]
    public function environment_type_and_slug_filters_apply_within_the_requested_application(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id, 'active', 'commerce-api');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);
        $productionEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f40';
        $stagingEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f41';
        $this->environment($organizationId, $applicationId, $user->identity_id, $productionEnvironmentId, 'Production', 'production', 'production', 'active', '2026-08-10T10:00:00+00:00');
        $this->environment($organizationId, $applicationId, $user->identity_id, $stagingEnvironmentId, 'Staging', 'staging', 'staging', 'active', '2026-08-10T11:00:00+00:00');

        $this->listEnvironments($user, $organizationId, $projectId, $applicationId, ['environment_type' => 'staging'])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $stagingEnvironmentId);
        $this->listEnvironments($user, $organizationId, $projectId, $applicationId, ['slug' => 'production'])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $productionEnvironmentId);
    }

    #[Test]
    public function invalid_and_unsupported_filters_are_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id, 'active', 'commerce-api');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);

        $this->listEnvironments($user, $organizationId, $projectId, $applicationId, ['environment_type' => 'disaster_recovery'])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.environment_type.0', 'The selected environment type is invalid.');
        $this->listEnvironments($user, $organizationId, $projectId, $applicationId, ['application_id' => $this->uuid()])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.application_id.0', 'The filter is not supported.');
    }

    #[Test]
    public function archived_projects_and_archived_applications_remain_available_for_authorized_historical_reads(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $archivedProjectId = $this->project($organizationId, $user->identity_id, 'archived', 'archived-project');
        $archivedProjectApplicationId = $this->application($organizationId, $archivedProjectId, $user->identity_id, 'active', 'historical-project-application');
        $activeProjectId = $this->project($organizationId, $user->identity_id, 'active', 'active-project');
        $archivedApplicationId = $this->application($organizationId, $activeProjectId, $user->identity_id, 'archived', 'archived-application');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);
        $archivedProjectEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f50';
        $archivedApplicationEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f51';
        $this->environment($organizationId, $archivedProjectApplicationId, $user->identity_id, $archivedProjectEnvironmentId, 'Historical Project', 'historical-project', 'testing', 'active', '2026-08-10T10:00:00+00:00');
        $this->environment($organizationId, $archivedApplicationId, $user->identity_id, $archivedApplicationEnvironmentId, 'Historical Application', 'historical-application', 'testing', 'active', '2026-08-10T11:00:00+00:00');

        $this->listEnvironments($user, $organizationId, $archivedProjectId, $archivedProjectApplicationId)->assertOk()
            ->assertJsonPath('data.0.id', $archivedProjectEnvironmentId);
        $this->listEnvironments($user, $organizationId, $activeProjectId, $archivedApplicationId)->assertOk()
            ->assertJsonPath('data.0.id', $archivedApplicationEnvironmentId);
    }

    #[Test]
    public function cross_organization_projects_and_applications_from_another_project_are_tenant_safely_unavailable(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $foreignProjectId = $this->project($otherOrganizationId, $user->identity_id, 'active', 'foreign-project');
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $otherProjectId = $this->project($organizationId, $user->identity_id, 'active', 'payments-project');
        $foreignApplicationId = $this->application($organizationId, $otherProjectId, $user->identity_id, 'active', 'payments-api');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);

        $this->listEnvironments($user, $organizationId, $foreignProjectId, $this->uuid())->assertNotFound()
            ->assertJsonPath('error.code', 'project.not_found');
        $this->listEnvironments($user, $organizationId, $projectId, $foreignApplicationId)->assertNotFound()
            ->assertJsonPath('error.code', 'application.not_found');
    }

    #[Test]
    public function an_active_member_without_environment_read_permission_cannot_list_production_environments(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id, 'active', 'commerce-api');
        $this->membership($organizationId, $user->identity_id);
        $this->environment($organizationId, $applicationId, $user->identity_id, '0198f3b6-48ae-7a44-9192-6f8029df5f60', 'Production', 'production', 'production', 'active', '2026-08-10T10:00:00+00:00');

        $this->listEnvironments($user, $organizationId, $projectId, $applicationId)->assertForbidden();
    }

    #[Test]
    public function an_unauthenticated_request_cannot_list_environments(): void
    {
        $this->getJson('/api/v1/organizations/'.$this->uuid().'/projects/'.$this->uuid().'/applications/'.$this->uuid().'/environments')->assertUnauthorized();
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Environment Reader',
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

    private function project(string $organizationId, string $identityId, string $status, string $slug): string
    {
        $projectId = $this->uuid();
        $now = now();
        ProjectRecord::query()->create([
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => 'Environment Project',
            'slug' => $slug,
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

    private function application(string $organizationId, string $projectId, string $identityId, string $status, string $slug): string
    {
        $applicationId = $this->uuid();
        $now = now();
        ApplicationRecord::query()->create([
            'id' => $applicationId,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'name' => 'Environment Application',
            'slug' => $slug,
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

    private function environment(string $organizationId, string $applicationId, string $identityId, string $environmentId, string $name, string $slug, string $environmentType, string $status, string $createdAt): void
    {
        $created = new DateTimeImmutable($createdAt);
        EnvironmentRecord::query()->create([
            'id' => $environmentId,
            'organization_id' => $organizationId,
            'application_id' => $applicationId,
            'name' => $name,
            'slug' => $slug,
            'environment_type' => $environmentType,
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $created,
            'updated_at' => $created,
            'archived_at' => $status === 'archived' ? $created : null,
            'version' => $status === 'archived' ? 2 : 1,
        ]);
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

    private function grantEnvironmentRead(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'environment.read')->value('id');
        self::assertIsString($permissionId);
        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'environment-reader-'.$roleId,
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

    /** @param array<string, string> $query */
    private function listEnvironments(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, string $applicationId, array $query = []): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)
            ->getJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications/'.$applicationId.'/environments'.($query === [] ? '' : '?'.http_build_query($query)));
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
