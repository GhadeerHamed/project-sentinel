<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Application\Domain\Entity\Application;
use Sentinel\Application\Domain\Exception\InvalidApplication;

final class ApplicationTest extends TestCase
{
    #[Test]
    public function it_creates_an_active_application_with_canonical_server_owned_fields_and_event(): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');
        $application = Application::create(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f10',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f11',
            projectId: '0198f3b6-48ae-7a45-9192-6f8029df5f12',
            name: ' Checkout API ',
            slug: 'CHECKOUT-API',
            description: ' Processes checkout requests. ',
            criticality: 'high',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f13',
            at: $at,
            eventId: '0198f3b6-48ae-7a45-9192-6f8029df5f14',
            correlationId: '0198f3b6-48ae-7a45-9192-6f8029df5f15',
            causationId: '0198f3b6-48ae-7a45-9192-6f8029df5f16',
        );

        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f10', $application->id()->value());
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f11', $application->organizationId()->value());
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f12', $application->projectId()->value());
        self::assertSame('Checkout API', $application->name());
        self::assertSame('checkout-api', $application->slug());
        self::assertSame('Processes checkout requests.', $application->description());
        self::assertSame('high', $application->criticality()->value);
        self::assertSame('active', $application->status()->value);
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f13', $application->createdByIdentityId()->value());
        self::assertSame($at, $application->createdAt());
        self::assertSame($at, $application->updatedAt());
        self::assertNull($application->archivedAt());
        self::assertSame(1, $application->version()->value());

        $events = $application->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertSame('application.created', $events[0]->eventName());
        self::assertSame([
            'project_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f12',
            'application_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f10',
            'actor_identity_id' => '0198f3b6-48ae-7a45-9192-6f8029df5f13',
            'application_name' => 'Checkout API',
            'application_slug' => 'checkout-api',
            'application_description' => 'Processes checkout requests.',
            'application_criticality' => 'high',
            'application_status' => 'active',
        ], $events[0]->payload());
    }

    #[Test]
    #[DataProvider('criticalities')]
    public function it_accepts_every_canonical_criticality_value(string $criticality): void
    {
        $application = Application::fromValues(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f20',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f21',
            projectId: '0198f3b6-48ae-7a45-9192-6f8029df5f22',
            name: 'Checkout API',
            slug: 'checkout-api',
            description: null,
            criticality: $criticality,
            status: 'active',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f23',
            createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            archivedAt: null,
            version: 1,
        );

        self::assertSame($criticality, $application->criticality()->value);
    }

    #[Test]
    public function it_rejects_an_invalid_criticality(): void
    {
        $this->expectException(InvalidApplication::class);

        Application::fromValues(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f30',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f31',
            projectId: '0198f3b6-48ae-7a45-9192-6f8029df5f32',
            name: 'Checkout API',
            slug: 'checkout-api',
            description: null,
            criticality: 'mission-critical',
            status: 'active',
            createdByIdentityId: '0198f3b6-48ae-7a45-9192-6f8029df5f33',
            createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            archivedAt: null,
            version: 1,
        );
    }

    /** @return array<string, array{string}> */
    public static function criticalities(): array
    {
        return [
            'critical' => ['critical'],
            'high' => ['high'],
            'medium' => ['medium'],
            'low' => ['low'],
        ];
    }
}
