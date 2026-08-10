<?php

declare(strict_types=1);

namespace Tests\Unit\Event\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Event\Domain\Entity\Event;
use Sentinel\Event\Domain\Exception\InvalidEvent;
use Sentinel\Event\Domain\ValueObject\EventEnvelope;
use Sentinel\Event\Domain\ValueObject\EventType;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class EventTest extends TestCase
{
    #[Test]
    public function canonical_envelopes_are_order_independent_and_accepted_events_are_immutable(): void
    {
        $first = EventEnvelope::fromValues(
            externalEventId: 'producer-1',
            eventType: 'application.error',
            schemaVersion: '1',
            severity: 'high',
            occurredAt: '2020-08-01T18:30:00+02:00',
            payload: ['z' => ['b' => 2, 'a' => 1], 'a' => ['first', 'second']],
            attributes: ['route' => '/checkout', 'attempt' => 1],
        );
        $second = EventEnvelope::fromValues(
            externalEventId: 'producer-1',
            eventType: 'application.error',
            schemaVersion: '1',
            severity: 'high',
            occurredAt: '2020-08-01T16:30:00Z',
            payload: ['a' => ['first', 'second'], 'z' => ['a' => 1, 'b' => 2]],
            attributes: ['attempt' => 1, 'route' => '/checkout'],
        );

        self::assertSame($first->normalizedHash(), $second->normalizedHash());
        self::assertSame(EventType::APPLICATION_ERROR, $first->eventType());
        self::assertSame('error', $first->eventType()->category()->value);

        $event = Event::accept(
            id: $this->uuid(),
            organizationId: $this->uuid(),
            projectId: $this->uuid(),
            applicationId: $this->uuid(),
            environmentId: $this->uuid(),
            eventSourceId: $this->uuid(),
            envelope: $first,
            acceptedAt: new DateTimeImmutable('2020-08-01T16:30:01Z'),
            correlationId: $this->uuid(),
        );

        self::assertTrue((new \ReflectionClass($event))->isReadOnly());
        self::assertSame('2020-08-01T16:30:00+00:00', $event->occurredAt()->format(DATE_ATOM));
        self::assertSame($event->receivedAt()->format(DATE_ATOM), $event->createdAt()->format(DATE_ATOM));
        self::assertFalse(method_exists($event, 'update'));
        self::assertFalse(method_exists($event, 'delete'));
    }

    #[Test]
    public function non_object_payloads_and_non_scalar_attributes_are_rejected(): void
    {
        $this->expectException(InvalidEvent::class);

        EventEnvelope::fromValues(
            externalEventId: null,
            eventType: 'application.error',
            schemaVersion: '1',
            severity: null,
            occurredAt: '2020-08-01T16:30:00Z',
            payload: ['not', 'an', 'object'],
        );
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
