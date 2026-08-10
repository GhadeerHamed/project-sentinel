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

final class GetProjectEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_retrieves_an_active_project_with_the_canonical_representation(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectRead($organizationId, $user->identity_id);
        $projectId = '0198f3b6-48ae-7a44-9192-6f8029df5f50';
        $this->project($organizationId, $user->identity_id, $projectId, 'Commerce Platform', 'commerce-platform', 'active', 3);

        $response = $this->getProject($user, $organizationId, $projectId);

        $response->assertOk()
            ->assertHeaderMissing('ETag')
            ->assertJsonPath('data.id', $projectId)
            ->assertJsonPath('data.type', 'project')
            ->assertJsonPath('data.attributes.name', 'Commerce Platform')
            ->assertJsonPath('data.attributes.slug', 'commerce-platform')
            ->assertJsonPath('data.attributes.description', 'Commerce Platform description')
            ->assertJsonPath('data.attributes.status', 'active')
            ->assertJsonPath('data.attributes.version', 3);
        $project = $response->json('data');
        self::assertIsArray($project);
        self::assertSame(['id', 'type', 'attributes'], array_keys($project));
        $attributes = $project['attributes'] ?? null;
        self::assertIsArray($attributes);
        self::assertSame(
            ['name', 'slug', 'description', 'status', 'version', 'created_at', 'updated_at'],
            array_keys($attributes),
        );
    }

    #[Test]
    public function an_archived_project_remains_available_for_authorized_historical_access(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectRead($organizationId, $user->identity_id);
        $projectId = '0198f3b6-48ae-7a44-9192-6f8029df5f51';
        $this->project($organizationId, $user->identity_id, $projectId, 'Archived Project', 'archived-project', 'archived', 4);

        $this->getProject($user, $organizationId, $projectId)->assertOk()
            ->assertJsonPath('data.id', $projectId)
            ->assertJsonPath('data.attributes.status', 'archived')
            ->assertJsonPath('data.attributes.version', 4);
    }

    #[Test]
    public function a_member_without_project_read_permission_cannot_retrieve_a_project(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $projectId = '0198f3b6-48ae-7a44-9192-6f8029df5f52';
        $this->project($organizationId, $user->identity_id, $projectId, 'Protected Project', 'protected-project', 'active', 1);

        $this->getProject($user, $organizationId, $projectId)->assertForbidden();
    }

    #[Test]
    public function missing_and_cross_organization_projects_are_tenant_safely_unavailable(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectRead($organizationId, $user->identity_id);
        $otherProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f53';
        $missingProjectId = '0198f3b6-48ae-7a44-9192-6f8029df5f54';
        $this->project($otherOrganizationId, $user->identity_id, $otherProjectId, 'Other Project', 'other-project', 'active', 1);

        $crossOrganization = $this->getProject($user, $organizationId, $otherProjectId)->assertNotFound();
        $missing = $this->getProject($user, $organizationId, $missingProjectId)->assertNotFound();

        self::assertSame($missing->json(), $crossOrganization->json());
        $crossOrganization->assertJsonPath('error.code', 'project.not_found');
    }

    #[Test]
    public function a_suspended_organization_cannot_be_used_to_retrieve_a_project(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id, 'suspended');
        $this->membership($organizationId, $user->identity_id);
        $this->grantProjectRead($organizationId, $user->identity_id);
        $projectId = '0198f3b6-48ae-7a44-9192-6f8029df5f55';
        $this->project($organizationId, $user->identity_id, $projectId, 'Suspended Organization Project', 'suspended-organization-project', 'active', 1);

        $this->getProject($user, $organizationId, $projectId)->assertNotFound();
    }

    #[Test]
    public function an_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/organizations/'.$this->uuid().'/projects/0198f3b6-48ae-7a44-9192-6f8029df5f56')->assertUnauthorized();
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

    private function project(string $organizationId, string $identityId, string $projectId, string $name, string $slug, string $status, int $version): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        ProjectRecord::query()->create([
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => $name,
            'slug' => $slug,
            'description' => $name.' description',
            'status' => $status,
            'created_by_identity_id' => $identityId,
            'created_at' => $at,
            'updated_at' => $at,
            'archived_at' => $status === 'archived' ? $at : null,
            'version' => $version,
        ]);
    }

    private function getProject(IdentityUserAuthenticatable $user, string $organizationId, string $projectId): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)->getJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
