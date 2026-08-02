<?php

declare(strict_types=1);

namespace Tests\Unit\IdentityAccess\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Exception\CrossOrganizationAssignment;
use Sentinel\IdentityAccess\Domain\Exception\InvalidMembershipTransition;

final class MembershipTest extends TestCase
{
    #[Test]
    public function it_enforces_membership_lifecycle_transitions_and_access_grants(): void
    {
        $membership = Membership::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d11',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d12',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d13',
            status: 'invited',
            createdAt: new DateTimeImmutable('2026-08-02T14:10:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:10:00+00:00'),
        );

        self::assertFalse($membership->grantsOrganizationAccess());

        $activated = $membership->activate(new DateTimeImmutable('2026-08-02T14:12:00+00:00'));
        self::assertSame('active', $activated->status()->value);
        self::assertTrue($activated->grantsOrganizationAccess());

        $suspended = $activated->suspend(new DateTimeImmutable('2026-08-02T14:13:00+00:00'));
        self::assertSame('suspended', $suspended->status()->value);
        self::assertFalse($suspended->grantsOrganizationAccess());

        $removed = $suspended->remove(new DateTimeImmutable('2026-08-02T14:14:00+00:00'));
        self::assertSame('removed', $removed->status()->value);
    }

    #[Test]
    public function it_rejects_invalid_membership_transitions(): void
    {
        $this->expectException(InvalidMembershipTransition::class);

        $membership = Membership::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d21',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d22',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d23',
            status: 'invited',
            createdAt: new DateTimeImmutable('2026-08-02T14:20:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:20:00+00:00'),
        );

        $membership->suspend(new DateTimeImmutable('2026-08-02T14:21:00+00:00'));
    }

    #[Test]
    public function it_rejects_cross_organization_membership_scope_usage(): void
    {
        $this->expectException(CrossOrganizationAssignment::class);

        $membership = Membership::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d31',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d32',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d33',
            status: 'active',
            createdAt: new DateTimeImmutable('2026-08-02T14:30:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:30:00+00:00'),
            activatedAt: new DateTimeImmutable('2026-08-02T14:30:00+00:00'),
        );

        $membership->assertOrganizationScope('01890f7a-5b7c-7cc0-98c4-dc0c0c073d99');
    }
}
