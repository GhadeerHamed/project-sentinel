<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Concurrency;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Concurrency\ConcurrencyConflict;
use Sentinel\Shared\Domain\Concurrency\CurrentVersion;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Concurrency\OptimisticConcurrencyConflict;

final class OptimisticConcurrencyPrimitivesTest extends TestCase
{
    #[Test]
    public function expected_version_matches_when_exact_value_equals_current_value(): void
    {
        $expected = ExpectedVersion::fromInt(3);
        $current = CurrentVersion::fromInt(3);

        self::assertTrue($expected->matches($current));
    }

    #[Test]
    public function expected_version_any_matches_any_current_version_state(): void
    {
        $expected = ExpectedVersion::any();

        self::assertTrue($expected->matches(CurrentVersion::none()));
        self::assertTrue($expected->matches(CurrentVersion::fromInt(7)));
    }

    #[Test]
    public function expected_version_none_matches_only_when_current_version_is_none(): void
    {
        $expected = ExpectedVersion::none();

        self::assertTrue($expected->matches(CurrentVersion::none()));
        self::assertFalse($expected->matches(CurrentVersion::fromInt(1)));
    }

    #[Test]
    public function it_throws_a_dedicated_conflict_when_expected_and_current_do_not_match(): void
    {
        $expected = ExpectedVersion::fromInt(5);
        $current = CurrentVersion::fromInt(4);

        try {
            $expected->assertMatches($current);
            self::fail('Expected optimistic concurrency conflict was not thrown.');
        } catch (OptimisticConcurrencyConflict $conflict) {
            self::assertInstanceOf(ConcurrencyConflict::class, $conflict);
            self::assertTrue($conflict->expectedVersion()->value()?->equals($expected->value()));
            self::assertTrue($conflict->currentVersion()->equals($current));
        }
    }

    #[Test]
    public function it_detects_conflict_when_expected_version_requires_existing_value_but_current_is_none(): void
    {
        $expected = ExpectedVersion::fromInt(1);
        $current = CurrentVersion::none();

        self::assertFalse($expected->matches($current));
    }
}
