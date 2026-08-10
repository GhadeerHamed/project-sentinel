<?php

declare(strict_types=1);

namespace Tests\Feature\Application;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
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

final class ArchiveApplicationEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_archives_an_active_application_with_a_quoted_etag(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f70';
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationArchive($organizationId, $user->identity_id);
        $this->application($organizationId, $projectId, $user->identity_id, $applicationId, 'active', 1);

        $this->archiveApplication($user, $organizationId, $projectId, $applicationId, 1)
            ->assertNoContent()
            ->assertHeader('ETag', '"2"');

        $application = ApplicationRecord::query()->findOrFail($applicationId);
        self::assertSame('archived', $application->status);
        self::assertNotNull($application->archived_at);
        self::assertSame(2, $application->version);
    }

    #[Test]
    public function an_already_archived_application_is_rejected_with_a_conflict(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f71';
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationArchive($organizationId, $user->identity_id);
        $this->application($organizationId, $projectId, $user->identity_id, $applicationId, 'archived', 2);

        $this->archiveApplication($user, $organizationId, $projectId, $applicationId, 2)
            ->assertConflict()
            ->assertJsonPath('error.code', 'request.conflict');
    }

    #[Test]
    public function a_missing_if_match_is_rejected(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f72';
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationArchive($organizationId, $user->identity_id);
        $this->application($organizationId, $projectId, $user->identity_id, $applicationId, 'active', 1);

        $this->archiveApplication($user, $organizationId, $projectId, $applicationId, null)->assertUnprocessable();
    }

    #[Test]
    public function a_stale_if_match_is_rejected_with_precondition_failed(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f73';
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationArchive($organizationId, $user->identity_id);
        $this->application($organizationId, $projectId, $user->identity_id, $applicationId, 'active', 2);

        $this->archiveApplication($user, $organizationId, $projectId, $applicationId, 1)
            ->assertStatus(412)
            ->assertJsonPath('error.code', 'concurrency.version_conflict');
    }

    #[Test]
    public function a_project_from_another_organization_is_not_found(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $foreignProjectId = $this->project($otherOrganizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f74';
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationArchive($organizationId, $user->identity_id);
        $this->application($otherOrganizationId, $foreignProjectId, $user->identity_id, $applicationId, 'active', 1);

        $this->archiveApplication($user, $organizationId, $foreignProjectId, $applicationId, 1)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'project.not_found');
    }

    #[Test]
    public function an_application_from_another_project_is_not_found(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $otherProjectId = $this->project($organizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f75';
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationArchive($organizationId, $user->identity_id);
        $this->application($organizationId, $otherProjectId, $user->identity_id, $applicationId, 'active', 1);

        $this->archiveApplication($user, $organizationId, $projectId, $applicationId, 1)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'application.not_found');
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Application Archiver',
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

    private function grantApplicationArchive(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'application.archive')->value('id');
        self::assertIsString($permissionId);

        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'application-archiver-'.$roleId,
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

    private function project(string $organizationId, string $identityId): string
    {
        $projectId = $this->uuid();
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        ProjectRecord::query()->create([
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => 'Commerce Platform',
            'slug' => 'commerce-platform-'.$projectId,
            'description' => 'Customer-facing commerce services.',
            'status' => 'active',
            'created_by_identity_id' => $identityId,
            'created_at' => $at,
            'updated_at' => $at,
            'archived_at' => null,
            'version' => 1,
        ]);

        return $projectId;
    }

    private function application(string $organizationId, string $projectId, string $identityId, string $applicationId, string $status, int $version): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        ApplicationRecord::query()->create([
            'id' => $applicationId,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'name' => 'Checkout API',
            'slug' => 'checkout-api-'.$applicationId,
            'description' => 'Processes checkout requests.',
            'criticality' => 'high',
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $at,
            'updated_at' => $at,
            'archived_at' => $status === 'archived' ? $at : null,
            'version' => $version,
        ]);
    }

    private function archiveApplication(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, string $applicationId, ?int $expectedVersion): TestResponse
    {
        $this->app->forgetScopedInstances();

        $request = $this->actingAs($user)
            ->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token');

        if ($expectedVersion !== null) {
            $request->withHeader('If-Match', sprintf('"%d"', $expectedVersion));
        }

        return $request->postJson(
            '/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications/'.$applicationId.'/archive',
        );
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
