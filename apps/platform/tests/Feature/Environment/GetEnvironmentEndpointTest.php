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

final class GetEnvironmentEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_retrieves_an_active_environment_with_only_the_canonical_public_representation(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f80';
        $this->environment($organizationId, $applicationId, $user->identity_id, $environmentId, 'Production', 'production', 'production');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);

        $response = $this->getEnvironment($user, $organizationId, $projectId, $applicationId, $environmentId);

        $response->assertOk()
            ->assertHeaderMissing('ETag')
            ->assertJsonPath('data.id', $environmentId)
            ->assertJsonPath('data.type', 'environment')
            ->assertJsonPath('data.attributes.application_id', $applicationId)
            ->assertJsonPath('data.attributes.name', 'Production')
            ->assertJsonPath('data.attributes.slug', 'production')
            ->assertJsonPath('data.attributes.environment_type', 'production')
            ->assertJsonPath('data.attributes.status', 'active')
            ->assertJsonPath('data.attributes.version', 1);
        $environment = $response->json('data');
        self::assertIsArray($environment);
        self::assertSame(['id', 'type', 'attributes'], array_keys($environment));
        $attributes = $environment['attributes'] ?? null;
        self::assertIsArray($attributes);
        self::assertSame(['application_id', 'name', 'slug', 'environment_type', 'status', 'version', 'created_at', 'updated_at'], array_keys($attributes));
    }

    #[Test]
    public function archived_environments_and_archived_parent_records_remain_readable_for_authorized_historical_access(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectWithArchivedEnvironmentId = $this->project($organizationId, $user->identity_id);
        $applicationWithArchivedEnvironmentId = $this->application($organizationId, $projectWithArchivedEnvironmentId, $user->identity_id);
        $archivedEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f81';
        $this->environment($organizationId, $applicationWithArchivedEnvironmentId, $user->identity_id, $archivedEnvironmentId, 'Archived', 'archived', 'testing', 'archived');
        $archivedProjectId = $this->project($organizationId, $user->identity_id, 'archived');
        $applicationUnderArchivedProjectId = $this->application($organizationId, $archivedProjectId, $user->identity_id);
        $environmentUnderArchivedProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f82';
        $this->environment($organizationId, $applicationUnderArchivedProjectId, $user->identity_id, $environmentUnderArchivedProjectId, 'Project History', 'project-history', 'staging');
        $activeProjectId = $this->project($organizationId, $user->identity_id);
        $archivedApplicationId = $this->application($organizationId, $activeProjectId, $user->identity_id, 'archived');
        $environmentUnderArchivedApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f83';
        $this->environment($organizationId, $archivedApplicationId, $user->identity_id, $environmentUnderArchivedApplicationId, 'Application History', 'application-history', 'development');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);

        $this->getEnvironment($user, $organizationId, $projectWithArchivedEnvironmentId, $applicationWithArchivedEnvironmentId, $archivedEnvironmentId)
            ->assertOk()->assertJsonPath('data.attributes.status', 'archived');
        $this->getEnvironment($user, $organizationId, $archivedProjectId, $applicationUnderArchivedProjectId, $environmentUnderArchivedProjectId)
            ->assertOk()->assertJsonPath('data.id', $environmentUnderArchivedProjectId);
        $this->getEnvironment($user, $organizationId, $activeProjectId, $archivedApplicationId, $environmentUnderArchivedApplicationId)
            ->assertOk()->assertJsonPath('data.id', $environmentUnderArchivedApplicationId);
    }

    #[Test]
    public function an_active_member_without_environment_read_permission_cannot_retrieve_a_production_environment(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f84';
        $this->environment($organizationId, $applicationId, $user->identity_id, $environmentId, 'Production', 'production', 'production');
        $this->membership($organizationId, $user->identity_id);

        $this->getEnvironment($user, $organizationId, $projectId, $applicationId, $environmentId)->assertForbidden();
    }

    #[Test]
    public function an_unauthenticated_actor_cannot_retrieve_an_environment(): void
    {
        $this->getJson('/api/v1/organizations/'.$this->uuid().'/projects/'.$this->uuid().'/applications/'.$this->uuid().'/environments/'.$this->uuid())
            ->assertUnauthorized();
    }

    #[Test]
    public function missing_and_cross_application_environments_are_tenant_safely_unavailable(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $otherApplicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $otherApplicationEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f85';
        $this->environment($organizationId, $otherApplicationId, $user->identity_id, $otherApplicationEnvironmentId, 'Other Production', 'other-production', 'production');
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);

        $missing = $this->getEnvironment($user, $organizationId, $projectId, $applicationId, '0198f3b6-48ae-7a44-9192-6f8029df5f86')->assertNotFound();
        $crossApplication = $this->getEnvironment($user, $organizationId, $projectId, $applicationId, $otherApplicationEnvironmentId)->assertNotFound();

        self::assertSame($missing->json(), $crossApplication->json());
        $missing->assertJsonPath('error.code', 'environment.not_found');
    }

    #[Test]
    public function cross_organization_projects_and_applications_from_another_project_are_tenant_safely_unavailable(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $otherProjectId = $this->project($organizationId, $user->identity_id);
        $applicationInOtherProjectId = $this->application($organizationId, $otherProjectId, $user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $foreignProjectId = $this->project($otherOrganizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentRead($organizationId, $user->identity_id);

        $this->getEnvironment($user, $organizationId, $foreignProjectId, $this->uuid(), $this->uuid())
            ->assertNotFound()->assertJsonPath('error.code', 'project.not_found');
        $this->getEnvironment($user, $organizationId, $projectId, $applicationInOtherProjectId, $this->uuid())
            ->assertNotFound()->assertJsonPath('error.code', 'application.not_found');
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill(['identity_id' => $this->uuid(), 'email' => $this->uuid().'@example.test', 'display_name' => 'Environment Reader', 'status' => 'active', 'version' => 1]);
        $user->save();

        return $user;
    }

    private function organization(string $identityId): string
    {
        $organizationId = $this->uuid();
        OrganizationRecord::query()->create(['id' => $organizationId, 'name' => 'Environment Organization', 'slug' => 'environment-organization-'.$organizationId, 'timezone' => 'UTC', 'created_by_identity_id' => $identityId, 'status' => 'active', 'version' => 1]);

        return $organizationId;
    }

    private function project(string $organizationId, string $identityId, string $status = 'active'): string
    {
        $projectId = $this->uuid();
        $now = now();
        ProjectRecord::query()->create(['id' => $projectId, 'organization_id' => $organizationId, 'name' => 'Environment Project', 'slug' => 'environment-project-'.$projectId, 'description' => null, 'status' => $status, 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => $status === 'archived' ? $now : null, 'version' => $status === 'archived' ? 2 : 1]);

        return $projectId;
    }

    private function application(string $organizationId, string $projectId, string $identityId, string $status = 'active'): string
    {
        $applicationId = $this->uuid();
        $now = now();
        ApplicationRecord::query()->create(['id' => $applicationId, 'organization_id' => $organizationId, 'project_id' => $projectId, 'name' => 'Environment Application', 'slug' => 'environment-application-'.$applicationId, 'description' => null, 'criticality' => 'high', 'status' => $status, 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => $status === 'archived' ? $now : null, 'version' => $status === 'archived' ? 2 : 1]);

        return $applicationId;
    }

    private function environment(string $organizationId, string $applicationId, string $identityId, string $environmentId, string $name, string $slug, string $environmentType, string $status = 'active'): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        EnvironmentRecord::query()->create(['id' => $environmentId, 'organization_id' => $organizationId, 'application_id' => $applicationId, 'name' => $name, 'slug' => $slug, 'environment_type' => $environmentType, 'status' => $status, 'created_by_identity_id' => $identityId, 'created_at' => $at, 'updated_at' => $at, 'archived_at' => $status === 'archived' ? $at : null, 'version' => $status === 'archived' ? 2 : 1]);
    }

    private function membership(string $organizationId, string $identityId): void
    {
        MembershipRecord::query()->create(['id' => $this->uuid(), 'organization_id' => $organizationId, 'user_identity_id' => $identityId, 'status' => 'active', 'activated_at' => now(), 'version' => 1]);
    }

    private function grantEnvironmentRead(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'environment.read')->value('id');
        self::assertIsString($permissionId);
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => 'environment-reader-'.$roleId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);
    }

    private function getEnvironment(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, string $applicationId, string $environmentId): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)->getJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications/'.$applicationId.'/environments/'.$environmentId);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
