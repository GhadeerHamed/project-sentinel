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

final class GetApplicationEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_member_retrieves_an_active_application_with_the_canonical_public_representation(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationRead($organizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f70';
        $this->application($organizationId, $projectId, $user->identity_id, $applicationId, 'active', 'critical', 3);

        $response = $this->getApplication($user, $organizationId, $projectId, $applicationId);

        $response->assertOk()
            ->assertHeaderMissing('ETag')
            ->assertJsonPath('data.id', $applicationId)
            ->assertJsonPath('data.type', 'application')
            ->assertJsonPath('data.attributes.project_id', $projectId)
            ->assertJsonPath('data.attributes.name', 'Checkout API')
            ->assertJsonPath('data.attributes.criticality', 'critical')
            ->assertJsonPath('data.attributes.status', 'active')
            ->assertJsonPath('data.attributes.version', 3);
        $application = $response->json('data');
        self::assertIsArray($application);
        self::assertSame(['id', 'type', 'attributes'], array_keys($application));
        $attributes = $application['attributes'] ?? null;
        self::assertIsArray($attributes);
        self::assertSame(['project_id', 'name', 'slug', 'description', 'criticality', 'status', 'version', 'created_at', 'updated_at'], array_keys($attributes));
    }

    #[Test]
    public function archived_applications_and_archived_parent_projects_remain_readable_for_authorized_historical_access(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id, 'archived');
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationRead($organizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f71';
        $this->application($organizationId, $projectId, $user->identity_id, $applicationId, 'archived', 'medium', 4);

        $this->getApplication($user, $organizationId, $projectId, $applicationId)->assertOk()
            ->assertJsonPath('data.attributes.status', 'archived')
            ->assertJsonPath('data.attributes.criticality', 'medium');
    }

    #[Test]
    public function an_unauthorized_actor_cannot_retrieve_an_application(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $applicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f72';
        $this->application($organizationId, $projectId, $user->identity_id, $applicationId, 'active', 'high', 1);
        $this->membership($organizationId, $user->identity_id);

        $this->getApplication($user, $organizationId, $projectId, $applicationId)->assertForbidden();
    }

    #[Test]
    public function an_unauthenticated_actor_cannot_retrieve_an_application(): void
    {
        $this->getJson('/api/v1/organizations/'.$this->uuid().'/projects/'.$this->uuid().'/applications/'.$this->uuid())->assertUnauthorized();
    }

    #[Test]
    public function missing_cross_organization_and_cross_project_applications_are_tenant_safely_unavailable(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $otherProjectId = $this->project($organizationId, $user->identity_id);
        $otherOrganizationId = $this->organization($user->identity_id);
        $foreignProjectId = $this->project($otherOrganizationId, $user->identity_id);
        $crossProjectApplicationId = '0198f3b6-48ae-7a44-9192-6f8029df5f73';
        $this->application($organizationId, $otherProjectId, $user->identity_id, $crossProjectApplicationId, 'active', 'low', 1);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationRead($organizationId, $user->identity_id);

        $missing = $this->getApplication($user, $organizationId, $projectId, '0198f3b6-48ae-7a44-9192-6f8029df5f74')->assertNotFound();
        $crossProject = $this->getApplication($user, $organizationId, $projectId, $crossProjectApplicationId)->assertNotFound();
        $crossOrganizationProject = $this->getApplication($user, $organizationId, $foreignProjectId, $crossProjectApplicationId)->assertNotFound();

        self::assertSame($missing->json(), $crossProject->json());
        $missing->assertJsonPath('error.code', 'application.not_found');
        $crossOrganizationProject->assertJsonPath('error.code', 'project.not_found');
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill(['identity_id' => $this->uuid(), 'email' => $this->uuid().'@example.test', 'display_name' => 'Application Reader', 'status' => 'active', 'version' => 1]);
        $user->save();

        return $user;
    }

    private function organization(string $identityId): string
    {
        $organizationId = $this->uuid();
        OrganizationRecord::query()->create(['id' => $organizationId, 'name' => 'Application Organization', 'slug' => 'application-organization-'.$organizationId, 'timezone' => 'UTC', 'created_by_identity_id' => $identityId, 'status' => 'active', 'version' => 1]);

        return $organizationId;
    }

    private function project(string $organizationId, string $identityId, string $status = 'active'): string
    {
        $projectId = $this->uuid();
        $now = now();
        ProjectRecord::query()->create(['id' => $projectId, 'organization_id' => $organizationId, 'name' => 'Application Project', 'slug' => 'application-project-'.$projectId, 'description' => null, 'status' => $status, 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => $status === 'archived' ? $now : null, 'version' => 1]);

        return $projectId;
    }

    private function application(string $organizationId, string $projectId, string $identityId, string $applicationId, string $status, string $criticality, int $version): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        ApplicationRecord::query()->create(['id' => $applicationId, 'organization_id' => $organizationId, 'project_id' => $projectId, 'name' => 'Checkout API', 'slug' => 'checkout-api-'.$applicationId, 'description' => 'Processes checkout requests.', 'criticality' => $criticality, 'status' => $status, 'created_by_identity_id' => $identityId, 'created_at' => $at, 'updated_at' => $at, 'archived_at' => $status === 'archived' ? $at : null, 'version' => $version]);
    }

    private function membership(string $organizationId, string $identityId): void
    {
        MembershipRecord::query()->create(['id' => $this->uuid(), 'organization_id' => $organizationId, 'user_identity_id' => $identityId, 'status' => 'active', 'activated_at' => now(), 'version' => 1]);
    }

    private function grantApplicationRead(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'application.read')->value('id');
        self::assertIsString($permissionId);
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => 'application-reader-'.$roleId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);
    }

    private function getApplication(IdentityUserAuthenticatable $user, string $organizationId, string $projectId, string $applicationId): TestResponse
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($user)->getJson('/api/v1/organizations/'.$organizationId.'/projects/'.$projectId.'/applications/'.$applicationId);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
