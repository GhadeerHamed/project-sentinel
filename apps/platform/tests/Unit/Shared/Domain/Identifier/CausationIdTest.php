<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Identifier;

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\InvalidResourceId;
use Sentinel\Shared\Domain\Identifier\ResourceId;

final class CausationIdTest extends TestCase
{
    private const UUID_V7 = '01890f7a-5b7c-7cc0-98c4-dc0c0c07398f';

    #[Test]
    public function it_creates_a_causation_id_from_a_uuid_v7(): void
    {
        $id = CausationId::fromString(self::UUID_V7);

        self::assertSame(self::UUID_V7, $id->value());
        self::assertSame(self::UUID_V7, (string) $id);
    }

    #[Test]
    public function it_normalizes_uppercase_values(): void
    {
        $id = CausationId::fromString(strtoupper(self::UUID_V7));

        self::assertSame(self::UUID_V7, $id->value());
    }

    #[Test]
    public function it_rejects_non_uuid_v7_values(): void
    {
        $this->expectException(InvalidResourceId::class);

        CausationId::fromString('550e8400-e29b-41d4-a716-446655440000');
    }

    #[Test]
    public function equality_requires_the_same_value_and_type(): void
    {
        $first = CausationId::fromString(self::UUID_V7);
        $same = CausationId::fromString(self::UUID_V7);
        $otherType = ExampleOtherIdForCausation::fromString(self::UUID_V7);

        self::assertTrue($first->equals($same));
        self::assertFalse($first->equals($otherType));
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function it_serializes_to_json_as_a_string(): void
    {
        $id = CausationId::fromString(self::UUID_V7);

        self::assertSame(
            '"'.self::UUID_V7.'"',
            json_encode($id, JSON_THROW_ON_ERROR),
        );
    }
}

final readonly class ExampleOtherIdForCausation extends ResourceId {}
