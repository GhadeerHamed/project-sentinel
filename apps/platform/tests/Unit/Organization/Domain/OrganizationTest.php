<?php

declare(strict_types=1);

namespace Tests\Unit\Organization\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Organization\Domain\Entity\Organization;
use Sentinel\Organization\Domain\Event\OrganizationArchived;
use Sentinel\Organization\Domain\Event\OrganizationCreated;
use Sentinel\Organization\Domain\Event\OrganizationReactivated;
use Sentinel\Organization\Domain\Event\OrganizationSuspended;
use Sentinel\Organization\Domain\Exception\InvalidOrganization;
use Sentinel\Organization\Domain\Exception\InvalidOrganizationTransition;

final class OrganizationTest extends TestCase
{
    #[Test]
    public function it_creates_an_organization_and_records_created_event(): void
    {
        $organization = Organization::create(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f01',
            name: 'Sentinel Org',
            slug: 'Sentinel-Org',
            createdByIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f02',
            at: new DateTimeImmutable('2026-08-02T15:00:00+00:00'),
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f03',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f04',
        );

        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073f01', $organization->id()->value());
        self::assertSame('Sentinel Org', $organization->name());
        self::assertSame('sentinel-org', $organization->slug());
        self::assertSame('active', $organization->status()->value);
        self::assertSame(1, $organization->version()->value());
        $createdEvents = $organization->pullDomainEvents();
        self::assertCount(1, $createdEvents);
        self::assertInstanceOf(OrganizationCreated::class, $createdEvents[0]);
        self::assertSame(1, $createdEvents[0]->aggregateVersion()->value());
        self::assertCount(0, $organization->pullDomainEvents());
    }

    #[Test]
    public function it_rejects_invalid_name_and_slug(): void
    {
        $this->expectException(InvalidOrganization::class);

        Organization::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f09',
            name: '   ',
            slug: 'invalid slug',
            status: 'active',
            createdByIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f10',
            createdAt: new DateTimeImmutable('2026-08-02T15:01:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T15:01:00+00:00'),
            version: 1,
        );
    }

    #[Test]
    public function it_enforces_lifecycle_transitions_increments_version_and_records_events(): void
    {
        $organization = Organization::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f11',
            name: 'Sentinel Org',
            slug: 'sentinel-org',
            status: 'active',
            createdByIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f12',
            createdAt: new DateTimeImmutable('2026-08-02T15:10:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T15:10:00+00:00'),
            version: 2,
        );

        $suspended = $organization->suspend(
            at: new DateTimeImmutable('2026-08-02T15:11:00+00:00'),
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f13',
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f14',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f15',
        );
        self::assertSame('suspended', $suspended->status()->value);
        self::assertSame(3, $suspended->version()->value());
        $suspendedEvents = $suspended->pullDomainEvents();
        self::assertCount(1, $suspendedEvents);
        self::assertInstanceOf(OrganizationSuspended::class, $suspendedEvents[0]);
        self::assertSame(3, $suspendedEvents[0]->aggregateVersion()->value());
        self::assertCount(0, $suspended->pullDomainEvents());

        $reactivated = $suspended->reactivate(
            at: new DateTimeImmutable('2026-08-02T15:12:00+00:00'),
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f13',
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f16',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f17',
        );
        self::assertSame('active', $reactivated->status()->value);
        self::assertSame(4, $reactivated->version()->value());
        $reactivatedEvents = $reactivated->pullDomainEvents();
        self::assertCount(1, $reactivatedEvents);
        self::assertInstanceOf(OrganizationReactivated::class, $reactivatedEvents[0]);
        self::assertSame(4, $reactivatedEvents[0]->aggregateVersion()->value());
        self::assertCount(0, $reactivated->pullDomainEvents());

        $archived = $reactivated->archive(
            at: new DateTimeImmutable('2026-08-02T15:13:00+00:00'),
            actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f13',
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f18',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f19',
        );
        self::assertSame('archived', $archived->status()->value);
        self::assertSame(5, $archived->version()->value());
        $archivedEvents = $archived->pullDomainEvents();
        self::assertCount(1, $archivedEvents);
        self::assertInstanceOf(OrganizationArchived::class, $archivedEvents[0]);
        self::assertSame(5, $archivedEvents[0]->aggregateVersion()->value());
        self::assertCount(0, $archived->pullDomainEvents());
    }

    #[Test]
    public function it_preserves_hydrated_resource_version(): void
    {
        $organization = Organization::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f20',
            name: 'Sentinel Org',
            slug: 'sentinel-org',
            status: 'active',
            createdByIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f21',
            createdAt: new DateTimeImmutable('2026-08-02T15:19:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T15:19:00+00:00'),
            version: 9,
        );

        self::assertSame(9, $organization->version()->value());
    }

    #[Test]
    public function it_rejects_invalid_organization_state_transitions_without_recording_events_or_incrementing_version(): void
    {
        $organization = Organization::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f21',
            name: 'Sentinel Org',
            slug: 'sentinel-org',
            status: 'archived',
            createdByIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f22',
            createdAt: new DateTimeImmutable('2026-08-02T15:20:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T15:20:00+00:00'),
            version: 1,
        );
        $versionBeforeFailure = $organization->version()->value();

        try {
            $organization->reactivate(
                at: new DateTimeImmutable('2026-08-02T15:21:00+00:00'),
                actorIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f22',
                eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f23',
                correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f24',
            );
            self::fail('Expected InvalidOrganizationTransition exception.');
        } catch (InvalidOrganizationTransition) {
            self::assertSame($versionBeforeFailure, $organization->version()->value());
            self::assertCount(0, $organization->pullDomainEvents());
        }
    }
}
