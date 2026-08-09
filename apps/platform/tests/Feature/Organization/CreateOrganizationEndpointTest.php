<?php

declare(strict_types=1);

namespace Tests\Feature\Organization;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\IdentityAccess\Records\OrganizationRoleRecord;
use App\Infrastructure\Persistence\IdentityAccess\Records\RoleAssignmentRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class CreateOrganizationEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authenticated_human_user_can_create_an_organization(): void
    {
        $user = $this->identityUser('active');

        $response = $this->actingAs($user, 'web')->csrfProtectedJsonPost([
            'name' => 'Acme Operations',
            'slug' => 'acme-operations',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => ['organization_id', 'membership_id', 'role_assignment_id'],
                'meta' => ['correlation_id'],
            ]);

        $organizationId = $response->json('data.organization_id');
        $membershipId = $response->json('data.membership_id');
        $roleAssignmentId = $response->json('data.role_assignment_id');
        $correlationId = $response->json('meta.correlation_id');

        self::assertIsString($organizationId);
        self::assertIsString($membershipId);
        self::assertIsString($roleAssignmentId);
        self::assertIsString($correlationId);
        $response->assertHeader('X-Correlation-ID', $correlationId);

        self::assertSame('acme-operations', OrganizationRecord::query()->findOrFail($organizationId)->slug);
        self::assertSame('active', MembershipRecord::query()->findOrFail($membershipId)->status);
        self::assertSame($organizationId, RoleAssignmentRecord::query()->findOrFail($roleAssignmentId)->organization_id);
        self::assertSame('organization_owner', OrganizationRoleRecord::query()->where('organization_id', $organizationId)->value('role_key'));
    }

    #[Test]
    public function a_founding_owner_can_update_the_organization_without_manually_seeded_permissions(): void
    {
        $user = $this->identityUser('active');

        $organizationId = $this->actingAs($user, 'web')->csrfProtectedJsonPost([
            'name' => 'Acme Operations',
            'slug' => 'acme-operations',
        ])->assertCreated()->json('data.organization_id');

        self::assertIsString($organizationId);

        $this->actingAs($user, 'web')
            ->csrfProtectedJsonPatch($organizationId, ['name' => 'Acme Platform Operations'], '"1"')
            ->assertOk()
            ->assertJsonPath('data.name', 'Acme Platform Operations');
    }

    #[Test]
    public function it_requires_session_authentication(): void
    {
        $this->csrfProtectedJsonPost([
            'name' => 'Acme Operations',
            'slug' => 'acme-operations',
        ])->assertUnauthorized()->assertExactJson([
            'error' => [
                'code' => 'authentication.required',
                'message' => 'Authentication is required.',
            ],
        ]);
    }

    #[Test]
    public function it_requires_csrf_protection_for_session_authenticated_requests(): void
    {
        $user = $this->identityUser('active');
        $this->app->instance('env', 'local');

        $this->actingAs($user, 'web')
            ->postJson('/api/v1/organizations', [
                'name' => 'Acme Operations',
                'slug' => 'acme-operations',
            ])
            ->assertStatus(419);
    }

    #[Test]
    public function it_returns_documented_validation_errors(): void
    {
        $user = $this->identityUser('active');

        $this->actingAs($user, 'web')->csrfProtectedJsonPost([
            'name' => '',
            'slug' => 'Invalid Slug',
            'timezone' => 'Europe/Bucharest',
        ])->assertUnprocessable()->assertExactJson([
            'error' => [
                'code' => 'request.validation_failed',
                'message' => 'The request contains invalid fields.',
                'details' => [
                    'fields' => [
                        'name' => ['The name field is required.'],
                        'slug' => ['The slug field format is invalid.'],
                        'timezone' => ['The field is not supported.'],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function it_maps_existing_organization_conflicts(): void
    {
        $user = $this->identityUser('active');
        OrganizationRecord::query()->create([
            'id' => $this->uuid(),
            'name' => 'Existing Organization',
            'slug' => 'acme-operations',
            'created_by_identity_id' => $user->identity_id,
            'status' => 'active',
            'version' => 1,
        ]);

        $this->actingAs($user, 'web')->csrfProtectedJsonPost([
            'name' => 'Acme Operations',
            'slug' => 'acme-operations',
        ])->assertStatus(412)->assertExactJson([
            'error' => [
                'code' => 'concurrency.version_conflict',
                'message' => 'The resource was modified by another operation.',
                'details' => [
                    'expected_version' => null,
                    'current_version' => null,
                ],
            ],
        ]);
    }

    private function identityUser(string $status): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => sprintf('%s@example.test', $this->uuid()),
            'display_name' => 'Organization Creator',
            'status' => $status,
            'version' => 1,
        ]);
        $user->save();

        return $user;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function csrfProtectedJsonPost(array $data): TestResponse
    {
        return $this->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->postJson('/api/v1/organizations', $data);
    }

    /**
     * @param  array<string, string>  $data
     */
    private function csrfProtectedJsonPatch(string $organizationId, array $data, string $ifMatch): TestResponse
    {
        return $this->withSession(['_token' => 'csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'csrf-token')
            ->withHeader('If-Match', $ifMatch)
            ->patchJson('/api/v1/organizations/'.$organizationId, $data);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
