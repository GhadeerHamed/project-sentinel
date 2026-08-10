<?php

declare(strict_types=1);

namespace Tests\Unit\Event\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Event\Domain\Entity\EventSource;
use Sentinel\Event\Domain\Exception\InvalidEventSource;

final class EventSourceTest extends TestCase
{
    #[Test]
    public function it_creates_an_active_api_client_source_for_one_hierarchy(): void
    {
        $at = new DateTimeImmutable('2026-08-10T12:00:00+00:00');

        $source = EventSource::create(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f10',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f11',
            projectId: '0198f3b6-48ae-7a45-9192-6f8029df5f12',
            applicationId: '0198f3b6-48ae-7a45-9192-6f8029df5f13',
            environmentId: '0198f3b6-48ae-7a45-9192-6f8029df5f14',
            name: ' Checkout API ',
            at: $at,
        );

        self::assertSame('api_client', $source->sourceType()->value);
        self::assertSame('active', $source->status()->value);
        self::assertTrue($source->isActive());
        self::assertSame('Checkout API', $source->name());
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f12', $source->projectId()->value());
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f13', $source->applicationId()->value());
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f14', $source->environmentId()->value());
        self::assertSame(1, $source->version()->value());
    }

    #[Test]
    public function it_rejects_non_api_client_source_types(): void
    {
        $this->expectException(InvalidEventSource::class);

        EventSource::fromValues(
            id: '0198f3b6-48ae-7a45-9192-6f8029df5f20',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f21',
            projectId: '0198f3b6-48ae-7a45-9192-6f8029df5f22',
            applicationId: '0198f3b6-48ae-7a45-9192-6f8029df5f23',
            environmentId: '0198f3b6-48ae-7a45-9192-6f8029df5f24',
            sourceType: 'plugin',
            name: 'Plugin Source',
            status: 'active',
            createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
            version: 1,
        );
    }
}
