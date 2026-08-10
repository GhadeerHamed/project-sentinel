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

final class ListProjectsEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_lists_only_active_projects_from_the_resolved_organization_in_canonical_order(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->membership($otherOrganizationId, $user->identity_id);
        $this->grantProjectRead($organizationId, $user->identity_id);
        $this->grantProjectRead($otherOrganizationId, $user->identity_id);

        $earlierProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f10';
        $laterProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f11';
        $otherProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f12';
        $this->project($organizationId, $user->identity_id, $earlierProjectId, 'Earlier Project', 'earlier-project', 'active', '2026-08-10T10:00:00+00:00');
        $this->project($organizationId, $user->identity_id, $laterProjectId, 'Later Project', 'later-project', 'active', '2026-08-10T11:00:00+00:00');
        $this->project($otherOrganizationId, $user->identity_id, $otherProjectId, 'Other Project', 'other-project', 'active', '2026-08-10T12:00:00+00:00');

        $response = $this->listProjects($user, $organizationId);

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $laterProjectId)
            ->assertJsonPath('data.1.id', $earlierProjectId)
            ->assertJsonMissing(['id' => $otherProjectId]);
        $firstProject = $response->json('data.0');
        self::assertIsArray($firstProject);
        self::assertSame(['id', 'type', 'attributes'], array_keys($firstProject));
        $attributes = $firstProject['attributes'] ?? null;
        self::assertIsArray($attributes);
        self::assertSame(
            ['name', 'slug', 'description', 'status', 'version', 'created_at', 'updated_at'],
            array_keys($attributes),
        );
        $response->assertJsonPath('pagination.has_more', false)
            ->assertJsonPath('pagination.next_cursor', null);
    }

    #[Test]
    public function the_project_list_uses_an_opaque_cursor_with_stable_canonical_ordering(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectRead($organizationId, $user->identity_id);
        $oldestProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f30';
        $middleProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f31';
        $newestProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f32';
        $this->project($organizationId, $user->identity_id, $oldestProjectId, 'Oldest Project', 'oldest-project', 'active', '2026-08-10T10:00:00+00:00');
        $this->project($organizationId, $user->identity_id, $middleProjectId, 'Middle Project', 'middle-project', 'active', '2026-08-10T11:00:00+00:00');
        $this->project($organizationId, $user->identity_id, $newestProjectId, 'Newest Project', 'newest-project', 'active', '2026-08-10T12:00:00+00:00');

        $firstPage = $this->listProjects($user, $organizationId, ['limit' => '1'])->assertOk()
            ->assertJsonPath('data.0.id', $newestProjectId)
            ->assertJsonPath('pagination.has_more', true);
        $nextCursor = $firstPage->json('pagination.next_cursor');
        self::assertIsString($nextCursor);

        $this->listProjects($user, $organizationId, ['limit' => '1', 'cursor' => $nextCursor])->assertOk()
            ->assertJsonPath('data.0.id', $middleProjectId)
            ->assertJsonPath('pagination.has_more', true);
    }

    #[Test]
    public function archived_projects_are_excluded_by_default_and_visible_when_explicitly_filtered(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectRead($organizationId, $user->identity_id);
        $activeProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f20';
        $archivedProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f21';
        $this->project($organizationId, $user->identity_id, $activeProjectId, 'Active Project', 'active-project', 'active', '2026-08-10T10:00:00+00:00');
        $this->project($organizationId, $user->identity_id, $archivedProjectId, 'Archived Project', 'archived-project', 'archived', '2026-08-10T11:00:00+00:00');

        $this->listProjects($user, $organizationId)->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeProjectId);
        $this->listProjects($user, $organizationId, ['status' => 'archived'])->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $archivedProjectId)
            ->assertJsonPath('data.0.attributes.status', 'archived');
    }

    #[Test]
    public function only_documented_filters_are_accepted(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectRead($organizationId, $user->identity_id);

        $this->listProjects($user, $organizationId, ['organization_id' => $this->uuid()])
            ->assertUnprocessable()
            ->assertJsonPath('error.details.fields.organization_id.0', 'The filter is not supported.');
    }

    #[Test]
    public function a_member_without_project_read_permission_cannot_list_projects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);

        $this->listProjects($user, $organizationId)->assertForbidden();
    }

    #[Test]
    public function a_suspended_organization_cannot_be_used_to_list_projects(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id, 'suspended');
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectRead($organizationId, $user->identity_id);

        $this->listProjects($user, $organizationId)->assertNotFound();
    }

    #[Test]
    public function an_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/organizations/'.$this->uuid().'/projects')->assertUnauthorized();
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Project Reader',
            'status' => 'active',
            'version' => 1,
        ]);
        $user->save();

        return $user;
    }

    private function organization(string $identityId, string $status = 'active'): string
    {
        $organizationId = $this->uuid();
        OrganizationRecord::query()->create([
            'id' => $organizationId,
            'name' => 'Project Organization',
            'slug' => 'project-organization-'.$organizationId,
            'timezone' => 'UTC',
            'created_by_identity_id' => $identityId,
            'status' => $status,
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

    private function grantProjectRead(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'project.read')->value('id');
        self::assertIsString($permissionId);

        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'project-reader-'.$roleId,
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

    private function project(string $organizationId, string $identityId, string $projectId, string $name, string $slug, string $status, string $createdAt): void
    {
        $created = new DateTimeImmutable($createdAt);
        ProjectRecord::query()->create([
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => $name,
            'slug' => $slug,
            'description' => $name.' description',
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $created,
            'updated_at' => $created,
            'archived_at' => $status === 'archived' ? $created : null,
            'version' => 1,
        ]);
    }

    /** @param array<string, string> $query */
    private function listProjects(IdentityUserAuthenticatable $user, string $organizationId, array $query = []): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)->getJson('/api/v1/organizations/'.$organizationId.'/projects'.($query === [] ? '' : '?'.http_build_query($query)));
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
