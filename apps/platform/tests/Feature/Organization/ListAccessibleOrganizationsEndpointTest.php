<?php

declare(strict_types=1);

namespace Tests\Feature\Organization;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class ListAccessibleOrganizationsEndpointTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_only_organizations_with_an_active_membership_and_keeps_suspended_organization_status(): void
    {
        $user = $this->user();
        $active = $this->organization($user->identity_id, 'active');
        $suspendedOrganization = $this->organization($user->identity_id, 'suspended');
        $other = $this->organization($this->user()->identity_id, 'active');
        $this->membership($active, $user->identity_id, 'active');
        $this->membership($suspendedOrganization, $user->identity_id, 'active');
        $this->membership($other, $user->identity_id, 'suspended');
        $this->membership($this->organization($user->identity_id, 'active'), $user->identity_id, 'removed');
        $this->membership($this->organization($user->identity_id, 'active'), $user->identity_id, 'invited');

        $response = $this->actingAs($user)->getJson('/api/v1/organizations');

        $response->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $active])
            ->assertJsonFragment(['id' => $suspendedOrganization, 'status' => 'suspended'])
            ->assertJsonMissing(['id' => $other]);
        self::assertArrayNotHasKey('roles', $response->json('data.0'));
        self::assertArrayNotHasKey('permissions', $response->json('data.0'));
        self::assertArrayNotHasKey('credentials', $response->json('data.0'));
    }

    #[Test]
    public function it_rejects_an_unauthenticated_request(): void
    {
        $this->getJson('/api/v1/organizations')->assertUnauthorized();
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill(['identity_id' => $this->uuid(), 'email' => $this->uuid().'@example.test', 'display_name' => 'User', 'status' => 'active', 'version' => 1]);
        $user->save();

        return $user;
    }

    private function organization(string $identityId, string $status): string
    {
        $id = $this->uuid();
        OrganizationRecord::query()->create(['id' => $id, 'name' => 'Acme', 'slug' => 'acme-'.$id, 'timezone' => 'UTC', 'created_by_identity_id' => $identityId, 'status' => $status, 'version' => 1]);

        return $id;
    }

    private function membership(string $organizationId, string $identityId, string $status): void
    {
        $now = now();
        MembershipRecord::query()->create(['id' => $this->uuid(), 'organization_id' => $organizationId, 'user_identity_id' => $identityId, 'status' => $status, 'activated_at' => $status === 'active' ? $now : null, 'suspended_at' => $status === 'suspended' ? $now : null, 'removed_at' => $status === 'removed' ? $now : null, 'version' => 1]);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
