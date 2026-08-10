<?php

declare(strict_types=1);

namespace Tests\Unit\Environment\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Environment\Domain\Entity\Environment;
use Sentinel\Environment\Domain\Exception\InvalidEnvironment;

final class EnvironmentTest extends TestCase
{
    #[Test]
    public function it_creates_an_active_environment_with_canonical_server_owned_fields_and_event(): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        $environment = Environment::create(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f10',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f11',
            applicationId: '0198f3b6-48ae-7a45-9192-6f8029df5f12',
            name: ' Production ',
            slug: 'PRODUCTION',
            environmentType: 'production',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f13',
            at: $at,
            eventId: '0198f3b6-48ae-7a45-9192-6f8029df5f14',
            correlationId: '0198f3b6-48ae-7a45-9192-6f8029df5f15',
            causationId: '0198f3b6-48ae-7a45-9192-6f8029df5f16',
        );

        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f10', $environment->id()->value());
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f11', $environment->organizationId()->value());
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f12', $environment->applicationId()->value());
        self::assertSame('Production', $environment->name());
        self::assertSame('production', $environment->slug());
        self::assertSame('production', $environment->environmentType()->value);
        self::assertSame('active', $environment->status()->value);
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f13', $environment->createdByIdentityId()->value());
        self::assertSame($at, $environment->createdAt());
        self::assertSame($at, $environment->updatedAt());
        self::assertNull($environment->archivedAt());
        self::assertSame(1, $environment->version()->value());

        $events = $environment->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertSame('environment.created', $events[0]->eventName());
        self::assertSame([
            'application_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f12',
            'environment_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f10',
            'actor_identity_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f13',
            'environment_name' => 'Production',
            'environment_slug' => 'production',
            'environment_type' => 'production',
            'environment_status' => 'active',
        ], $events[0]->payload());
    }

    #[Test]
    #[DataProvider('environmentTypes')]
    public function it_accepts_every_canonical_environment_type(string $environmentType): void
    {
        $environment = Environment::fromValues(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f20',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f21',
            applicationId: '0198f3b6-48ae-7a45-9192-6f8029df5f22',
            name: 'Checkout Environment',
            slug: 'checkout-environment',
            environmentType: $environmentType,
            status: 'active',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f23',
            createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            archivedAt: null,
            version: 1,
        );

        self::assertSame($environmentType, $environment->environmentType()->value);
    }

    #[Test]
    public function it_rejects_an_invalid_environment_type(): void
    {
        $this->expectException(InvalidEnvironment::class);

        Environment::fromValues(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f30',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f31',
            applicationId: '0198f3b6-48ae-7a45-9192-6f8029df5f32',
            name: 'Checkout Environment',
            slug: 'checkout-environment',
            environmentType: 'disaster_recovery',
            status: 'active',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f33',
            createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            archivedAt: null,
            version: 1,
        );
    }

    /** @return array<string, array{string}> */
    public static function environmentTypes(): array
    {
        return [
            'production' => ['production'],
            'staging' => ['staging'],
            'testing' => ['testing'],
            'development' => ['development'],
            'sandbox' => ['sandbox'],
            'other' => ['other'],
        ];
    }
}
