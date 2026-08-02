<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Identifier;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identifier\ResourceId;

final class CorrelationIdTest extends TestCase
{
    private const UUID_V7 = '01890f7a-5b7c-7cc0-98c4-dc0c0c07398f';

    #[Test]
    public function it_creates_a_typed_correlation_identifier(): void
    {
        $id = CorrelationId::fromString(self::UUID_V7);

        self::assertInstanceOf(ResourceId::class, $id);
        self::assertSame(self::UUID_V7, $id->value());
    }

    #[Test]
    public function it_is_not_equal_to_another_identifier_type(): void
    {
        $correlationId = CorrelationId::fromString(self::UUID_V7);
        $otherId = OtherIdentifier::fromString(self::UUID_V7);

        self::assertFalse($correlationId->equals($otherId));
    }
}

final readonly class OtherIdentifier extends ResourceId {}
