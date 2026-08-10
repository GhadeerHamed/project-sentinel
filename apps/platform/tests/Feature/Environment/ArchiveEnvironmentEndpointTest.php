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

final class ArchiveEnvironmentEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_archives_an_active_environment_with_a_quoted_etag(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f90';
        $this->environment($organizationId, $applicationId, $user->identity_id, $environmentId, 'production', 'active', 1);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentArchive($organizationId, $user->identity_id);

        $this->archiveEnvironment($user, $organizationId, $projectId, $applicationId, $environmentId, 1)
            ->assertNoContent()
            ->assertHeader('ETag', '"2"');

        $environment = EnvironmentRecord::query()->findOrFail($environmentId);
        self::assertSame('archived', $environment->status);
        self::assertNotNull($environment->archived_at);
        self::assertSame(2, $environment->version);
    }

    #[Test]
    public function active_environments_can_be_archived_beneath_archived_parents(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $archivedProjectId = $this->project($organizationId, $user->identity_id, 'archived');
        $activeApplicationId = $this->application($organizationId, $archivedProjectId, $user->identity_id);
        $archivedProjectEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f91';
        $this->environment($organizationId, $activeApplicationId, $user->identity_id, $archivedProjectEnvironmentId, 'testing', 'active', 1);
        $activeProjectId = $this->project($organizationId, $user->identity_id);
        $archivedApplicationId = $this->application($organizationId, $activeProjectId, $user->identity_id, 'archived');
        $archivedApplicationEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f92';
        $this->environment($organizationId, $archivedApplicationId, $user->identity_id, $archivedApplicationEnvironmentId, 'staging', 'active', 1);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentArchive($organizationId, $user->identity_id);

        $this->archiveEnvironment($user, $organizationId, $archivedProjectId, $activeApplicationId, $archivedProjectEnvironmentId, 1)->assertNoContent();
        $this->archiveEnvironment($user, $organizationId, $activeProjectId, $archivedApplicationId, $archivedApplicationEnvironmentId, 1)->assertNoContent();
    }

    #[Test]
    public function an_active_member_without_environment_archive_permission_cannot_archive_a_production_environment(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f93';
        $this->environment($organizationId, $applicationId, $user->identity_id, $environmentId, 'production', 'active', 1);
        $this->membership($organizationId, $user->identity_id);

        $this->archiveEnvironment($user, $organizationId, $projectId, $applicationId, $environmentId, 1)->assertForbidden();
        $this->assertDatabaseHas('environments', ['id' => $environmentId, 'status' => 'active', 'version' => 1, 'archived_at' => null]);
    }

    #[Test]
    public function an_unauthenticated_request_cannot_archive_an_environment(): void
    {
        $this->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->postJson('/api/v1/organizations/'.$this->uuid().'/projects/'.$this->uuid().'/applications/'.$this->uuid().'/environments/'.$this->uuid().'/archive')
            ->assertUnauthorized();
    }

    #[Test]
    public function a_missing_environment_is_not_found(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentArchive($organizationId, $user->identity_id);

        $this->archiveEnvironment($user, $organizationId, $projectId, $applicationId, $this->uuid(), 1)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'environment.not_found');
    }

    #[Test]
    public function cross_organization_projects_cross_project_applications_and_cross_application_environments_are_tenant_safely_unavailable(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $otherProjectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $applicationInOtherProjectId = $this->application($organizationId, $otherProjectId, $user->identity_id);
        $otherApplicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $otherApplicationEnvironmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f94';
        $this->environment($organizationId, $otherApplicationId, $user->identity_id, $otherApplicationEnvironmentId, 'production', 'active', 1);
        $otherOrganizationId = $this->organization($user->identity_id);
        $foreignProjectId = $this->project($otherOrganizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentArchive($organizationId, $user->identity_id);

        $this->archiveEnvironment($user, $organizationId, $foreignProjectId, $this->uuid(), $this->uuid(), 1)
            ->assertNotFound()->assertJsonPath('error.code', 'project.not_found');
        $this->archiveEnvironment($user, $organizationId, $projectId, $applicationInOtherProjectId, $this->uuid(), 1)
            ->assertNotFound()->assertJsonPath('error.code', 'application.not_found');
        $missing = $this->archiveEnvironment($user, $organizationId, $projectId, $applicationId, $this->uuid(), 1)->assertNotFound();
        $crossApplication = $this->archiveEnvironment($user, $organizationId, $projectId, $applicationId, $otherApplicationEnvironmentId, 1)->assertNotFound();

        self::assertSame($missing->json(), $crossApplication->json());
        $missing->assertJsonPath('error.code', 'environment.not_found');
    }

    #[Test]
    public function an_already_archived_environment_is_rejected_with_a_conflict(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f95';
        $this->environment($organizationId, $applicationId, $user->identity_id, $environmentId, 'testing', 'archived', 2);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentArchive($organizationId, $user->identity_id);

        $this->archiveEnvironment($user, $organizationId, $projectId, $applicationId, $environmentId, 2)
            ->assertConflict()
            ->assertJsonPath('error.code', 'request.conflict');
    }

    #[Test]
    public function a_missing_if_match_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f96';
        $this->environment($organizationId, $applicationId, $user->identity_id, $environmentId, 'testing', 'active', 1);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentArchive($organizationId, $user->identity_id);

        $this->archiveEnvironment($user, $organizationId, $projectId, $applicationId, $environmentId, null)->assertUnprocessable();
    }

    #[Test]
    public function a_stale_if_match_is_rejected_with_precondition_failed(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = $this->application($organizationId, $projectId, $user->identity_id);
        $environmentId = '0198f3b6-48ae-7a44-9192-6f8029df5f97';
        $this->environment($organizationId, $applicationId, $user->identity_id, $environmentId, 'testing', 'active', 2);
        $this->membership($organizationId, $user->identity_id);
        $this->grantEnvironmentArchive($organizationId, $user->identity_id);

        $this->archiveEnvironment($user, $organizationId, $projectId, $applicationId, $environmentId, 1)
            ->assertStatus(412)
            ->assertJsonPath('error.code', 'concurrency.version_conflict');
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill(['identity_id' => $this->uuid(), 'email' => $this->uuid().'@example.test', 'display_name' => 'Environment Archiver', 'status' => 'active', 'version' => 1]);
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

    private function environment(string $organizationId, string $applicationId, string $identityId, string $environmentId, string $environmentType, string $status, int $version): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        EnvironmentRecord::query()->create(['id' => $environmentId, 'organization_id' => $organizationId, 'application_id' => $applicationId, 'name' => ucfirst($environmentType), 'slug' => $environmentType.'-'.$environmentId, 'environment_type' => $environmentType, 'status' => $status, 'created_by_identity_id' => $identityId, 'created_at' => $at, 'updated_at' => $at, 'archived_at' => $status === 'archived' ? $at : null, 'version' => $version]);
    }

    private function membership(string $organizationId, string $identityId): void
    {
        MembershipRecord::query()->create(['id' => $this->uuid(), 'organization_id' => $organizationId, 'user_identity_id' => $identityId, 'status' => 'active', 'activated_at' => now(), 'version' => 1]);
    }

    private function grantEnvironmentArchive(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'environment.archive')->value('id');
        self::assertIsString($permissionId);
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => 'environment-archiver-'.$roleId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);
    }

    private function archiveEnvironment(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, string $applicationId, string $environmentId, ?int $expectedVersion): TestResponse
    {
        $this->app->forgetScopedInstances();
        $request = $this->actingAs($user)
            ->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token');

        if ($expectedVersion !== null) {
            $request->withHeader('If-Match', sprintf('"%d"', $expectedVersion));
        }

        return $request->postJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications/'.$applicationId.'/environments/'.$environmentId.'/archive');
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
