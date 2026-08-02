<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Event;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Sentinel\Shared\Domain\Event\DomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class DomainEventContractTest extends TestCase
{
    #[Test]
    public function it_defines_the_shared_domain_event_contract_shape(): void
    {
        $contract = new ReflectionClass(DomainEvent::class);

        self::assertTrue($contract->isInterface());

        $this->assertMethodReturnType($contract, 'eventId', EventId::class, false);
        $this->assertMethodReturnType($contract, 'organizationId', OrganizationId::class, false);
        $this->assertMethodReturnType($contract, 'occurredAt', DateTimeImmutable::class, false);
        $this->assertMethodReturnType($contract, 'correlationId', CorrelationId::class, false);
        $this->assertMethodReturnType($contract, 'causationId', CausationId::class, true);
        $this->assertMethodReturnType($contract, 'eventName', 'string', false);
        $this->assertMethodReturnType($contract, 'eventVersion', 'int', false);
    }

    #[Test]
    public function it_supports_a_strongly_typed_event_envelope_usage(): void
    {
        $occurredAt = new DateTimeImmutable('2026-08-02T00:00:00+00:00');

        $event = ExampleDomainEvent::fromValues(
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073a01',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073a02',
            occurredAt: $occurredAt,
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073a03',
            causationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073a04',
            eventName: 'alert.created',
            eventVersion: 1,
        );

        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073a01', $event->eventId()->value());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073a02', $event->organizationId()->value());
        self::assertSame($occurredAt, $event->occurredAt());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073a03', $event->correlationId()->value());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073a04', $event->causationId()?->value());
        self::assertSame('alert.created', $event->eventName());
        self::assertSame(1, $event->eventVersion());
    }

    private function assertMethodReturnType(
        ReflectionClass $contract,
        string $methodName,
        string $expectedType,
        bool $expectedNullable,
    ): void {
        $method = $contract->getMethod($methodName);
        $returnType = $method->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $returnType);
        self::assertSame($expectedType, $returnType->getName());
        self::assertSame($expectedNullable, $returnType->allowsNull());
    }
}

final readonly class ExampleDomainEvent implements DomainEvent
{
    private function __construct(
        private EventId $eventId,
        private OrganizationId $organizationId,
        private DateTimeImmutable $occurredAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
        private string $eventName,
        private int $eventVersion,
    ) {}

    public static function fromValues(
        string $eventId,
        string $organizationId,
        DateTimeImmutable $occurredAt,
        string $correlationId,
        ?string $causationId,
        string $eventName,
        int $eventVersion,
    ): self {
        return new self(
            eventId: EventId::fromString($eventId),
            organizationId: OrganizationId::fromString($organizationId),
            occurredAt: $occurredAt,
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
            eventName: $eventName,
            eventVersion: $eventVersion,
        );
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->causationId;
    }

    public function eventName(): string
    {
        return $this->eventName;
    }

    public function eventVersion(): int
    {
        return $this->eventVersion;
    }
}
