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

final class ListApplicationsEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_lists_only_active_applications_for_the_requested_project_in_canonical_order(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $otherProjectId = $this->project($organizationId, $user->identity_id, 'active', 'payments-project');
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationRead($organizationId, $user->identity_id);
        $earlierApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f10';
        $laterApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f11';
        $otherApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f12';
        $this->application($organizationId, $projectId, $user->identity_id, $earlierApplicationId, 'Earlier API', 'earlier-api', 'high', 'active', '2026-08-10T10:00:00+00:00');
        $this->application($organizationId, $projectId, $user->identity_id, $laterApplicationId, 'Later API', 'later-api', 'critical', 'active', '2026-08-10T11:00:00+00:00');
        $this->application($organizationId, $otherProjectId, $user->identity_id, $otherApplicationId, 'Other API', 'other-api', 'low', 'active', '2026-08-10T12:00:00+00:00');

        $response = $this->listApplications($user, $organizationId, $projectId);

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $laterApplicationId)
            ->assertJsonPath('data.1.id', $earlierApplicationId)
            ->assertJsonPath('data.0.attributes.project_id', $projectId)
            ->assertJsonPath('data.0.attributes.criticality', 'critical')
            ->assertJsonMissing(['id' => $otherApplicationId])
            ->assertJsonPath('pagination.has_more', false)
            ->assertJsonPath('pagination.next_cursor', null);
        $firstApplication = $response->json('data.0');
        self::assertIsArray($firstApplication);
        self::assertSame(['id', 'type', 'attributes'], array_keys($firstApplication));
        $attributes = $firstApplication['attributes'] ?? null;
        self::assertIsArray($attributes);
        self::assertSame(
            ['project_id', 'name', 'slug', 'description', 'criticality', 'status', 'version', 'created_at', 'updated_at'],
            array_keys($attributes),
        );
    }

    #[Test]
    public function the_application_list_uses_an_opaque_cursor_with_stable_canonical_ordering(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationRead($organizationId, $user->identity_id);
        $oldestApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f20';
        $middleApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f21';
        $newestApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f22';
        $this->application($organizationId, $projectId, $user->identity_id, $oldestApplicationId, 'Oldest API', 'oldest-api', 'low', 'active', '2026-08-10T10:00:00+00:00');
        $this->application($organizationId, $projectId, $user->identity_id, $middleApplicationId, 'Middle API', 'middle-api', 'medium', 'active', '2026-08-10T11:00:00+00:00');
        $this->application($organizationId, $projectId, $user->identity_id, $newestApplicationId, 'Newest API', 'newest-api', 'high', 'active', '2026-08-10T12:00:00+00:00');

        $firstPage = $this->listApplications($user, $organizationId, $projectId, ['limit' => '1'])->assertOk()
            ->assertJsonPath('data.0.id', $newestApplicationId)
            ->assertJsonPath('pagination.has_more', true);
        $nextCursor = $firstPage->json('pagination.next_cursor');
        self::assertIsString($nextCursor);

        $this->listApplications($user, $organizationId, $projectId, ['limit' => '1', 'cursor' => $nextCursor])->assertOk()
            ->assertJsonPath('data.0.id', $middleApplicationId)
            ->assertJsonPath('pagination.has_more', true);
    }

    #[Test]
    public function archived_applications_are_excluded_by_default_and_available_to_authorized_historical_reads_when_explicitly_filtered(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationRead($organizationId, $user->identity_id);
        $activeApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f30';
        $archivedApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f31';
        $this->application($organizationId, $projectId, $user->identity_id, $activeApplicationId, 'Active API', 'active-api', 'high', 'active', '2026-08-10T10:00:00+00:00');
        $this->application($organizationId, $projectId, $user->identity_id, $archivedApplicationId, 'Archived API', 'archived-api', 'high', 'archived', '2026-08-10T11:00:00+00:00');

        $this->listApplications($user, $organizationId, $projectId)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeApplicationId);
        $this->listApplications($user, $organizationId, $projectId, ['status' => 'archived'])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $archivedApplicationId)
            ->assertJsonPath('data.0.attributes.status', 'archived');
    }

    #[Test]
    public function an_archived_parent_project_remains_available_for_authorized_application_historical_reads(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'archived', 'archived-commerce-project');
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationRead($organizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f40';
        $this->application($organizationId, $projectId, $user->identity_id, $applicationId, 'Historical API', 'historical-api', 'medium', 'active', '2026-08-10T10:00:00+00:00');

        $this->listApplications($user, $organizationId, $projectId)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $applicationId);
    }

    #[Test]
    public function a_project_from_another_organization_is_tenant_safely_unavailable(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $foreignProjectId = $this->project($otherOrganizationId, $user->identity_id, 'active', 'foreign-project');
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationRead($organizationId, $user->identity_id);

        $this->listApplications($user, $organizationId, $foreignProjectId)->assertNotFound()
            ->assertJsonPath('error.code', 'project.not_found');
    }

    #[Test]
    public function only_documented_project_scoped_filters_are_accepted(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationRead($organizationId, $user->identity_id);

        $this->listApplications($user, $organizationId, $projectId, ['project_id' => $this->uuid()])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.project_id.0', 'The filter is not supported.');
    }

    #[Test]
    public function an_active_member_without_application_read_permission_cannot_list_applications(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'active', 'commerce-project');
        $this->membership($organizationId, $user->identity_id);

        $this->listApplications($user, $organizationId, $projectId)->assertForbidden();
    }

    #[Test]
    public function an_unauthenticated_request_cannot_list_applications(): void
    {
        $this->getJson('/api/v1/organizations/'.$this->uuid().'/projects/'.$this->uuid().'/applications')->assertUnauthorized();
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Application Reader',
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

    private function project(string $organizationId, string $identityId, string $status, string $slug): string
    {
        $projectId = $this->uuid();
        $now = now();
        ProjectRecord::query()->create([
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => 'Application Project',
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

    private function application(string $organizationId, string $projectId, string $identityId, string $applicationId, string $name, string $slug, string $criticality, string $status, string $createdAt): void
    {
        $created = new DateTimeImmutable($createdAt);
        ApplicationRecord::query()->create([
            'id' => $applicationId,
            'organization_id' => $organizationId,
            'project_id' => $projectId,
            'name' => $name,
            'slug' => $slug,
            'description' => $name.' description',
            'criticality' => $criticality,
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

    private function grantApplicationRead(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'application.read')->value('id');
        self::assertIsString($permissionId);
        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'application-reader-'.$roleId,
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
    private function listApplications(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, array $query = []): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)
            ->getJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications'.($query === [] ? '' : '?'.http_build_query($query)));
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
