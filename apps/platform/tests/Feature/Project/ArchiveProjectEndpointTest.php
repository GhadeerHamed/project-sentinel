<?php

declare(strict_types=1);

namespace Tests\Feature\Project;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
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

final class ArchiveProjectEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_archives_an_active_project_with_an_etag_outbox_and_audit_record(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = '0198f3b6-48ae-7a44-9192-6f8029df5f60';
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectArchive($organizationId, $user->identity_id);
        $this->project($organizationId, $user->identity_id, $projectId, 'active', 1);

        $this->archiveProject($user, $organizationId, $projectId, 1)
            ->assertNoContent()
            ->assertHeader('ETag', '"2"');

        $project = ProjectRecord::query()->findOrFail($projectId);
        self::assertSame('archived', $project->status);
        self::assertNotNull($project->archived_at);
        self::assertSame(2, $project->version);
        $this->assertDatabaseHas('outbox_messages', [
            'organization_id' => $organizationId,
            'event_type' => 'project.archived',
            'aggregate_type' => 'project',
            'aggregate_id' => $projectId,
        ]);
        $this->assertDatabaseHas('audit_records', [
            'organization_id' => $organizationId,
            'action' => 'project.archive',
            'resource_type' => 'project',
            'resource_id' => $projectId,
        ]);
    }

    #[Test]
    public function tenant_access_without_project_archive_permission_cannot_archive_a_project_or_write_side_effects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = '0198f3b6-48ae-7a44-9192-6f8029df5f61';
        $this->membership($organizationId, $user->identity_id);
        $this->project($organizationId, $user->identity_id, $projectId, 'active', 1);

        $this->archiveProject($user, $organizationId, $projectId, 1)->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $projectId, 'status' => 'active', 'version' => 1, 'archived_at' => null]);
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseCount('audit_records', 0);
    }

    #[Test]
    public function an_unauthenticated_request_is_rejected(): void
    {
        $this->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->postJson(
                '/api/v1/organizations/'.$this->uuid().'/projects/0198f3b6-48ae-7a44-9192-6f8029df5f62/archive',
                [],
                ['If-Match' => '"1"'],
            )->assertUnauthorized();
    }

    #[Test]
    public function cross_organization_and_missing_projects_are_tenant_safely_unavailable(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $otherProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f63';
        $missingProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f64';
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectArchive($organizationId, $user->identity_id);
        $this->project($otherOrganizationId, $user->identity_id, $otherProjectId, 'active', 1);

        $crossOrganization = $this->archiveProject($user, $organizationId, $otherProjectId, 1)->assertNotFound();
        $missing = $this->archiveProject($user, $organizationId, $missingProjectId, 1)->assertNotFound();

        self::assertSame($missing->json(), $crossOrganization->json());
        $crossOrganization->assertJsonPath('error.code', 'project.not_found');
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseCount('audit_records', 0);
    }

    #[Test]
    public function an_already_archived_project_is_rejected_without_writing_side_effects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = '0198f3b6-48ae-7a44-9192-6f8029df5f65';
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectArchive($organizationId, $user->identity_id);
        $this->project($organizationId, $user->identity_id, $projectId, 'archived', 2);

        $this->archiveProject($user, $organizationId, $projectId, 2)
            ->assertConflict()
            ->assertJsonPath('error.code', 'request.conflict');

        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseCount('audit_records', 0);
    }

    #[Test]
    public function missing_or_stale_if_match_is_rejected_without_mutating_the_project_or_writing_side_effects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = '0198f3b6-48ae-7a44-9192-6f8029df5f66';
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectArchive($organizationId, $user->identity_id);
        $this->project($organizationId, $user->identity_id, $projectId, 'active', 2);

        $this->archiveProject($user, $organizationId, $projectId, null)->assertUnprocessable();
        $this->archiveProject($user, $organizationId, $projectId, 1)
            ->assertStatus(412)
            ->assertJsonPath('error.code', 'concurrency.version_conflict');

        $this->assertDatabaseHas('projects', ['id' => $projectId, 'status' => 'active', 'version' => 2, 'archived_at' => null]);
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseCount('audit_records', 0);
    }

    #[Test]
    public function server_owned_project_fields_are_not_accepted_from_the_archive_request_body(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = '0198f3b6-48ae-7a44-9192-6f8029df5f67';
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectArchive($organizationId, $user->identity_id);
        $this->project($organizationId, $user->identity_id, $projectId, 'active', 1);

        $this->archiveProject($user, $organizationId, $projectId, 1, [
            'organization_id' => $this->uuid(),
            'status' => 'archived',
            'archived_at' => '2026-08-10T15:00:00Z',
            'version' => 99,
        ])->assertUnprocessable()
            ->assertJsonPath('error.details.fields.organization_id.0', 'The field is not supported.');

        $this->assertDatabaseHas('projects', ['id' => $projectId, 'status' => 'active', 'version' => 1, 'archived_at' => null]);
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseCount('audit_records', 0);
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Project Archiver',
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
        MembershipRecord::query()->create([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'user_identity_id' => $identityId,
            'status' => 'active',
            'activated_at' => now(),
            'version' => 1,
        ]);
    }

    private function grantProjectArchive(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'project.archive')->value('id');
        self::assertIsString($permissionId);

        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'project-archiver-'.$roleId,
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

    private function project(string $organizationId, string $identityId, string $projectId, string $status, int $version): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        ProjectRecord::query()->create([
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => 'Commerce Platform',
            'slug' => 'commerce-platform-'.$projectId,
            'description' => 'Customer-facing commerce services.',
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $at,
            'updated_at' => $at,
            'archived_at' => $status === 'archived' ? $at : null,
            'version' => $version,
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function archiveProject(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, ?int $expectedVersion, array $payload = []): TestResponse
    {
        $this->app->forgetScopedInstances();

        $request = $this->actingAs($user)
            ->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token');

        if ($expectedVersion !== null) {
            $request->withHeader('If-Match', sprintf('"%d"', $expectedVersion));
        }

        return $request->postJson(
            '/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/archive',
            $payload,
        );
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
