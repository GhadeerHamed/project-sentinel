<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Pagination;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;

final class CreatedAtIdCursorTest extends TestCase
{
    private const ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c07398f';

    #[Test]
    public function it_round_trips_a_created_at_and_resource_id_with_its_filter_scope(): void
    {
        $createdAt = new DateTimeImmutable('2026-08-10T12:34:56.123456+00:00');
        $cursor = CreatedAtIdCursor::fromValues($createdAt, self::ID);

        $decoded = CreatedAtIdCursor::fromEncoded($cursor->encode('filter-hash'), 'filter-hash');

        self::assertEquals($createdAt, $decoded->createdAt());
        self::assertSame(self::ID, $decoded->id());
    }

    #[Test]
    public function it_rejects_a_cursor_for_a_different_filter_scope(): void
    {
        $cursor = CreatedAtIdCursor::fromValues(
            new DateTimeImmutable('2026-08-10T12:34:56.123456+00:00'),
            self::ID,
        );

        $this->expectException(InvalidArgumentException::class);

        CreatedAtIdCursor::fromEncoded($cursor->encode('original-filter'), 'different-filter');
    }

    #[Test]
    public function it_rejects_a_cursor_with_a_non_uuid_v7_id(): void
    {
        $payload = base64_encode(json_encode([
            'created_at' => '2026-08-10T12:34:56.123456+00:00',
            'id' => 'not-a-resource-id',
            'filter_hash' => 'filter-hash',
        ], JSON_THROW_ON_ERROR));

        $this->expectException(InvalidArgumentException::class);

        CreatedAtIdCursor::fromEncoded(rtrim(strtr($payload, '+/', '-_'), '='), 'filter-hash');
    }
}
