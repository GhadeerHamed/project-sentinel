<?php

declare(strict_types=1);

namespace Tests\Application\IdentityAccess;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\PermissionAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;

final class OrganizationPermissionAuthorizerTest extends TestCase
{
    #[Test]
    public function it_allows_an_active_member_with_the_required_effective_permission(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075001';
        $identityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075002';
        $context = TenantContext::fromValues($organizationId, $identityId, 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c075003');
        $at = new DateTimeImmutable('2026-08-08T12:00:00+00:00');
        $memberships = $this->createMock(MembershipRepository::class);
        $memberships->expects(self::once())->method('getByIdentity')->with(
            self::callback(static fn ($organization): bool => $organization->value() === $organizationId),
            self::callback(static fn ($identity): bool => $identity->value() === $identityId),
        )->willReturn($this->activeMembership($organizationId, $identityId));
        $permissions = $this->createMock(PermissionAssignmentRepository::class);
        $permissions->expects(self::once())->method('permissionsForIdentity')->with(
            self::callback(static fn ($organization): bool => $organization->value() === $organizationId),
            self::callback(static fn ($identity): bool => $identity->value() === $identityId),
            $at,
        )->willReturn([PermissionCode::fromString('organization.member.manage')]);
        $clock = $this->createMock(Clock::class);
        $clock->expects(self::once())->method('now')->willReturn($at);

        $allowed = (new OrganizationPermissionAuthorizer($memberships, $permissions, $clock))->allows(
            $context,
            PermissionCode::fromString('organization.member.manage'),
        );

        self::assertTrue($allowed);
    }

    #[Test]
    public function it_denies_when_the_acting_identity_has_no_membership_in_the_tenant(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075004';
        $identityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075005';
        $context = TenantContext::fromValues($organizationId, $identityId, 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c075006');
        $memberships = $this->createMock(MembershipRepository::class);
        $memberships->expects(self::once())->method('getByIdentity')->willThrowException(MembershipNotFound::forIdentity(
            IdentityId::fromString($identityId),
            OrganizationId::fromString($organizationId),
        ));
        $permissions = $this->createMock(PermissionAssignmentRepository::class);
        $permissions->expects(self::never())->method('permissionsForIdentity');
        $clock = $this->createMock(Clock::class);
        $clock->expects(self::never())->method('now');

        $allowed = (new OrganizationPermissionAuthorizer($memberships, $permissions, $clock))->allows(
            $context,
            PermissionCode::fromString('organization.member.manage'),
        );

        self::assertFalse($allowed);
    }

    #[Test]
    public function it_denies_a_non_active_membership_without_looking_up_permissions(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075007';
        $identityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075008';
        $context = TenantContext::fromValues($organizationId, $identityId, 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c075009');
        $at = new DateTimeImmutable('2026-08-08T12:00:00+00:00');
        $memberships = $this->createMock(MembershipRepository::class);
        $memberships->method('getByIdentity')->willReturn(Membership::fromValues(
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075010',
            $organizationId,
            $identityId,
            'suspended',
            $at,
            $at,
            $at,
            $at,
        ));
        $permissions = $this->createMock(PermissionAssignmentRepository::class);
        $permissions->expects(self::never())->method('permissionsForIdentity');
        $clock = $this->createMock(Clock::class);
        $clock->expects(self::never())->method('now');

        $allowed = (new OrganizationPermissionAuthorizer($memberships, $permissions, $clock))->allows(
            $context,
            PermissionCode::fromString('organization.member.manage'),
        );

        self::assertFalse($allowed);
    }

    #[Test]
    public function it_denies_when_the_required_permission_is_not_effective(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075011';
        $identityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075012';
        $context = TenantContext::fromValues($organizationId, $identityId, 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c075013');
        $at = new DateTimeImmutable('2026-08-08T12:00:00+00:00');
        $memberships = $this->createMock(MembershipRepository::class);
        $memberships->method('getByIdentity')->willReturn($this->activeMembership($organizationId, $identityId));
        $permissions = $this->createMock(PermissionAssignmentRepository::class);
        $permissions->method('permissionsForIdentity')->willReturn([PermissionCode::fromString('organization.update')]);
        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn($at);

        $allowed = (new OrganizationPermissionAuthorizer($memberships, $permissions, $clock))->allows(
            $context,
            PermissionCode::fromString('organization.member.manage'),
        );

        self::assertFalse($allowed);
    }

    private function activeMembership(string $organizationId, string $identityId): Membership
    {
        $at = new DateTimeImmutable('2026-08-08T12:00:00+00:00');

        return Membership::fromValues(
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075014',
            $organizationId,
            $identityId,
            'active',
            $at,
            $at,
            $at,
        );
    }
}
