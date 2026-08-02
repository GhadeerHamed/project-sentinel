<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Time;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Time\SystemClock;

final class SystemClockTest extends TestCase
{
    #[Test]
    public function it_returns_an_immutable_datetime_in_utc(): void
    {
        $clock = new SystemClock;

        $now = $clock->now();

        self::assertInstanceOf(DateTimeImmutable::class, $now);
        self::assertSame('UTC', $now->getTimezone()->getName());
    }

    #[Test]
    public function it_returns_a_new_instance_for_each_call(): void
    {
        $clock = new SystemClock;

        $first = $clock->now();
        $second = $clock->now();

        self::assertNotSame($first, $second);
    }
}
