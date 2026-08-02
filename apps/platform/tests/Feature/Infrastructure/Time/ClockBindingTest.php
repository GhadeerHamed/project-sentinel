<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Time;

use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Time\SystemClock;
use Tests\TestCase;

final class ClockBindingTest extends TestCase
{
    #[Test]
    public function it_resolves_the_system_clock_for_the_clock_contract(): void
    {
        $clock = $this->app->make(Clock::class);

        self::assertInstanceOf(
            SystemClock::class,
            $clock,
        );
    }
}
