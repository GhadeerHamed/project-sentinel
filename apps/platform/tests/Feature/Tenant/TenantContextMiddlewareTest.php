<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Http\Middleware\ResolveTenantContext;
use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Tests\TestCase;

final class TenantContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth', ResolveTenantContext::class])
            ->get('/_testing/organizations/{organization}/tenant-context', function (TenantContext $tenantContext): JsonResponse {
                return response()->json([
                    'organization_id' => $tenantContext->organizationId()->value(),
                    'identity_id' => $tenantContext->actingIdentity()->identityId(),
                ]);
            });
    }

    #[Test]
    public function it_injects_a_tenant_context_for_an_active_membership(): void
    {
        $user = $this->identityUser();
        $organizationId = $this->organization('active', $user->identity_id);
        $this->membership($organizationId, $user->identity_id, 'active');

        $this->actingAs($user)
            ->getJson(sprintf('/_testing/organizations/%s/tenant-context', $organizationId))
            ->assertOk()
            ->assertExactJson(['organization_id' => $organizationId, 'identity_id' => $user->identity_id]);
    }

    #[Test]
    public function it_hides_inactive_organizations(): void
    {
        $user = $this->identityUser();
        $organizationId = $this->organization('suspended', $user->identity_id);
        $this->membership($organizationId, $user->identity_id, 'active');

        $this->actingAs($user)
            ->getJson(sprintf('/_testing/organizations/%s/tenant-context', $organizationId))
            ->assertNotFound();
    }

    #[Test]
    public function it_hides_missing_suspended_and_removed_memberships(): void
    {
        $user = $this->identityUser();
        $missingMembershipOrganizationId = $this->organization('active', $user->identity_id);

        $this->actingAs($user)
            ->getJson(sprintf('/_testing/organizations/%s/tenant-context', $missingMembershipOrganizationId))
            ->assertNotFound();

        foreach (['suspended', 'removed'] as $status) {
            $organizationId = $this->organization('active', $user->identity_id);
            $this->membership($organizationId, $user->identity_id, $status);

            $this->actingAs($user)
                ->getJson(sprintf('/_testing/organizations/%s/tenant-context', $organizationId))
                ->assertNotFound();
        }
    }

    private function identityUser(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => sprintf('%s@example.test', $this->uuid()),
            'display_name' => 'Tenant User',
            'status' => 'active',
            'version' => 1,
        ]);
        $user->save();

        return $user;
    }

    private function organization(string $status, string $identityId): string
    {
        $organizationId = $this->uuid();

        OrganizationRecord::query()->create([
            'id' => $organizationId,
            'name' => 'Tenant Organization',
            'slug' => sprintf('tenant-%s', $organizationId),
            'created_by_identity_id' => $identityId,
            'status' => $status,
            'version' => 1,
        ]);

        return $organizationId;
    }

    private function membership(string $organizationId, string $identityId, string $status): void
    {
        $at = now();

        MembershipRecord::query()->create([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'user_identity_id' => $identityId,
            'status' => $status,
            'activated_at' => $status === 'active' ? $at : null,
            'suspended_at' => $status === 'suspended' ? $at : null,
            'removed_at' => $status === 'removed' ? $at : null,
            'version' => 1,
        ]);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
