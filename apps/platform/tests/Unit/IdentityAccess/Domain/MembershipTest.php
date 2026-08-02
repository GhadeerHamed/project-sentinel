<?php

declare(strict_types=1);

namespace Tests\Unit\IdentityAccess\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Event\MembershipActivated;
use Sentinel\IdentityAccess\Domain\Event\MembershipCreated;
use Sentinel\IdentityAccess\Domain\Event\MembershipInvited;
use Sentinel\IdentityAccess\Domain\Event\MembershipRemoved;
use Sentinel\IdentityAccess\Domain\Event\MembershipSuspended;
use Sentinel\IdentityAccess\Domain\Exception\CrossOrganizationAssignment;
use Sentinel\IdentityAccess\Domain\Exception\InvalidMembershipTransition;

final class MembershipTest extends TestCase
{
    #[Test]
    public function it_records_membership_lifecycle_events_and_advances_resource_version(): void
    {
        $membership = Membership::invite(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d11',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d12',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d13',
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d14',
            at: new DateTimeImmutable('2026-08-02T14:10:00+00:00'),
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d15',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d16',
        );

        self::assertFalse($membership->grantsOrganizationAccess());
        self::assertSame(1, $membership->version()->value());

        $createdEvents = $membership->pullDomainEvents();
        self::assertCount(1, $createdEvents);
        self::assertInstanceOf(MembershipInvited::class, $createdEvents[0]);
        self::assertCount(0, $membership->pullDomainEvents());

        $activated = $membership->activate(
            at: new DateTimeImmutable('2026-08-02T14:12:00+00:00'),
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d14',
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d17',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d18',
        );
        self::assertSame('active', $activated->status()->value);
        self::assertTrue($activated->grantsOrganizationAccess());
        self::assertNotNull($activated->activatedAt());
        self::assertSame(2, $activated->version()->value());

        $activatedEvents = $activated->pullDomainEvents();
        self::assertCount(1, $activatedEvents);
        self::assertInstanceOf(MembershipActivated::class, $activatedEvents[0]);
        self::assertCount(0, $activated->pullDomainEvents());

        $suspended = $activated->suspend(
            at: new DateTimeImmutable('2026-08-02T14:13:00+00:00'),
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d14',
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d19',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d20',
        );
        self::assertSame('suspended', $suspended->status()->value);
        self::assertFalse($suspended->grantsOrganizationAccess());
        self::assertNotNull($suspended->suspendedAt());
        self::assertSame(3, $suspended->version()->value());

        $suspendedEvents = $suspended->pullDomainEvents();
        self::assertCount(1, $suspendedEvents);
        self::assertInstanceOf(MembershipSuspended::class, $suspendedEvents[0]);
        self::assertCount(0, $suspended->pullDomainEvents());

        $removed = $suspended->remove(
            at: new DateTimeImmutable('2026-08-02T14:14:00+00:00'),
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d14',
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d21',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d22',
        );
        self::assertSame('removed', $removed->status()->value);
        self::assertNotNull($removed->removedAt());
        self::assertSame(4, $removed->version()->value());

        $removedEvents = $removed->pullDomainEvents();
        self::assertCount(1, $removedEvents);
        self::assertInstanceOf(MembershipRemoved::class, $removedEvents[0]);
        self::assertCount(0, $removed->pullDomainEvents());
    }

    #[Test]
    public function it_establishes_founding_membership_as_active_without_invitation_event(): void
    {
        $membership = Membership::establishActive(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073daa',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073dab',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073dac',
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073dad',
            at: new DateTimeImmutable('2026-08-02T14:15:00+00:00'),
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073dae',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073daf',
        );

        self::assertSame('active', $membership->status()->value);
        self::assertSame(1, $membership->version()->value());

        $events = $membership->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(MembershipCreated::class, $events[0]);
        self::assertSame(1, $events[0]->aggregateVersion()->value());
        self::assertSame('membership.created', $events[0]->eventName());
    }

    #[Test]
    public function it_preserves_hydrated_resource_version(): void
    {
        $membership = Membership::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d81',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d82',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d83',
            status: 'active',
            createdAt: new DateTimeImmutable('2026-08-02T14:52:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:52:00+00:00'),
            activatedAt: new DateTimeImmutable('2026-08-02T14:52:00+00:00'),
            version: 7,
        );

        self::assertSame(7, $membership->version()->value());
    }

    #[Test]
    public function it_rejects_invalid_membership_transitions_without_changing_version_or_recording_events(): void
    {
        $membership = Membership::invite(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d21',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d22',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d23',
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d24',
            at: new DateTimeImmutable('2026-08-02T14:20:00+00:00'),
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d25',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d26',
        );

        $versionBeforeFailure = $membership->version()->value();
        $membership->pullDomainEvents();

        try {
            $membership->suspend(
                at: new DateTimeImmutable('2026-08-02T14:21:00+00:00'),
                actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d24',
                eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d27',
                correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d28',
            );
            self::fail('Expected InvalidMembershipTransition exception.');
        } catch (InvalidMembershipTransition) {
            self::assertSame($versionBeforeFailure, $membership->version()->value());
            self::assertCount(0, $membership->pullDomainEvents());
        }
    }

    #[Test]
    public function it_rejects_activation_from_removed_state(): void
    {
        $this->expectException(InvalidMembershipTransition::class);

        $membership = Membership::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d41',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d42',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d43',
            status: 'removed',
            createdAt: new DateTimeImmutable('2026-08-02T14:40:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:40:00+00:00'),
            removedAt: new DateTimeImmutable('2026-08-02T14:40:00+00:00'),
            version: 3,
        );

        $membership->activate(
            at: new DateTimeImmutable('2026-08-02T14:41:00+00:00'),
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d44',
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d45',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d46',
        );
    }

    #[Test]
    public function it_requires_status_specific_membership_timestamps(): void
    {
        $this->expectException(InvalidMembershipTransition::class);

        Membership::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d51',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d52',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d53',
            status: 'active',
            createdAt: new DateTimeImmutable('2026-08-02T14:50:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:50:00+00:00'),
            version: 1,
        );
    }

    #[Test]
    public function it_requires_suspended_timestamp_for_suspended_memberships(): void
    {
        $this->expectException(InvalidMembershipTransition::class);

        Membership::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d61',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d62',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d63',
            status: 'suspended',
            createdAt: new DateTimeImmutable('2026-08-02T14:51:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:51:00+00:00'),
            activatedAt: new DateTimeImmutable('2026-08-02T14:51:00+00:00'),
            version: 2,
        );
    }

    #[Test]
    public function it_requires_removed_timestamp_for_removed_memberships(): void
    {
        $this->expectException(InvalidMembershipTransition::class);

        Membership::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d71',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d72',
            userIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d73',
            status: 'removed',
            createdAt: new DateTimeImmutable('2026-08-02T14:52:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:52:00+00:00'),
            activatedAt: new DateTimeImmutable('2026-08-02T14:52:00+00:00'),
            version: 3,
        );
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
            version: 2,
        );

        $membership->assertOrganizationScope('01890f7a-5b7c-7cc0-98c4-dc0c0c073d99');
    }
}
