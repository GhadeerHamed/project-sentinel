<?php

declare(strict_types=1);

namespace Tests\Unit\Organization\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Organization\Domain\Entity\Organization;
use Sentinel\Organization\Domain\Exception\InvalidOrganizationTransition;

final class OrganizationTest extends TestCase
{
    #[Test]
    public function it_creates_an_immutable_organization_and_enforces_slug_rules(): void
    {
        $organization = Organization::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f01',
            name: 'Sentinel Org',
            slug: 'Sentinel-Org',
            status: 'active',
            createdByIdentityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073f02',
            createdAt: new DateTimeImmutable('2026-08-02T15:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T15:00:00+00:00'),
            version: 1,
        );

        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073f01', $organization->id()->value());
        self::assertSame('Sentinel Org', $organization->name());
        self::assertSame('sentinel-org', $organization->slug());
        self::assertSame('active', $organization->status()->value);
        self::assertSame(1, $organization->version()->value());
    }

    #[Test]
    public function it_enforces_lifecycle_transitions_and_increments_resource_version(): void
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

        $suspended = $organization->suspend(new DateTimeImmutable('2026-08-02T15:11:00+00:00'));
        self::assertSame('suspended', $suspended->status()->value);
        self::assertSame(3, $suspended->version()->value());

        $reactivated = $suspended->reactivate(new DateTimeImmutable('2026-08-02T15:12:00+00:00'));
        self::assertSame('active', $reactivated->status()->value);
        self::assertSame(4, $reactivated->version()->value());

        $archived = $reactivated->archive(new DateTimeImmutable('2026-08-02T15:13:00+00:00'));
        self::assertSame('archived', $archived->status()->value);
        self::assertSame(5, $archived->version()->value());
    }

    #[Test]
    public function it_rejects_invalid_organization_state_transitions(): void
    {
        $this->expectException(InvalidOrganizationTransition::class);

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

        $organization->reactivate(new DateTimeImmutable('2026-08-02T15:21:00+00:00'));
    }
}
