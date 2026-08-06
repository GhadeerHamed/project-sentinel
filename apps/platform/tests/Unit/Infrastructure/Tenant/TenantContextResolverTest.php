<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Tenant;

use App\Infrastructure\Tenant\TenantContextResolutionFailed;
use App\Infrastructure\Tenant\TenantContextResolver;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\Organization\Domain\Entity\Organization;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Shared\Domain\Identity\IdentityReference;

final class TenantContextResolverTest extends TestCase
{
    private const ORGANIZATION_ID = '01902e9b-50c1-7000-8000-000000000001';

    private const IDENTITY_ID = '01902e9b-50c1-7000-8000-000000000002';

    private const CORRELATION_ID = '01902e9b-50c1-7000-8000-000000000003';

    #[Test]
    public function it_resolves_a_tenant_context_for_an_active_organization_membership(): void
    {
        $context = $this->resolver($this->organization(), $this->membership())->resolve(
            identity: IdentityReference::fromString(self::IDENTITY_ID, 'user'),
            requestedOrganizationId: self::ORGANIZATION_ID,
            correlationId: self::CORRELATION_ID,
        );

        self::assertSame(self::ORGANIZATION_ID, $context->organizationId()->value());
        self::assertSame(self::IDENTITY_ID, $context->actingIdentity()->identityId());
    }

    #[Test]
    #[DataProvider('inactiveOrganizationStatuses')]
    public function it_rejects_inactive_organizations(string $status): void
    {
        $this->expectException(TenantContextResolutionFailed::class);

        $this->resolver($this->organization(status: $status), $this->membership())->resolve(
            IdentityReference::fromString(self::IDENTITY_ID, 'user'),
            self::ORGANIZATION_ID,
            self::CORRELATION_ID,
        );
    }

    #[Test]
    #[DataProvider('nonAccessMemberships')]
    public function it_rejects_non_access_memberships(string $status, ?string $organizationId, ?string $identityId): void
    {
        $this->expectException(TenantContextResolutionFailed::class);

        $this->resolver(
            $this->organization(),
            $this->membership(status: $status, organizationId: $organizationId, identityId: $identityId),
        )->resolve(
            IdentityReference::fromString(self::IDENTITY_ID, 'user'),
            self::ORGANIZATION_ID,
            self::CORRELATION_ID,
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function inactiveOrganizationStatuses(): array
    {
        return ['suspended' => ['suspended'], 'archived' => ['archived']];
    }

    /**
     * @return array<string, array{string, string|null, string|null}>
     */
    public static function nonAccessMemberships(): array
    {
        return [
            'suspended' => ['suspended', null, null],
            'removed' => ['removed', null, null],
            'cross organization' => ['active', '01902e9b-50c1-7000-8000-000000000004', null],
            'other identity' => ['active', null, '01902e9b-50c1-7000-8000-000000000005'],
        ];
    }

    private function resolver(Organization $organization, Membership $membership): TenantContextResolver
    {
        $organizations = $this->createStub(OrganizationRepository::class);
        $organizations->method('getById')->willReturn($organization);
        $memberships = $this->createStub(MembershipRepository::class);
        $memberships->method('getByIdentity')->willReturn($membership);

        return new TenantContextResolver($organizations, $memberships);
    }

    private function organization(string $status = 'active'): Organization
    {
        $at = new DateTimeImmutable('2026-08-06T12:00:00+00:00');

        return Organization::fromValues(self::ORGANIZATION_ID, 'Sentinel', 'sentinel', $status, self::IDENTITY_ID, $at, $at, 1);
    }

    private function membership(string $status = 'active', ?string $organizationId = null, ?string $identityId = null): Membership
    {
        $at = new DateTimeImmutable('2026-08-06T12:00:00+00:00');

        return Membership::fromValues(
            '01902e9b-50c1-7000-8000-000000000006',
            $organizationId ?? self::ORGANIZATION_ID,
            $identityId ?? self::IDENTITY_ID,
            $status,
            $at,
            $at,
            $status === 'active' ? $at : null,
            $status === 'suspended' ? $at : null,
            $status === 'removed' ? $at : null,
        );
    }
}
