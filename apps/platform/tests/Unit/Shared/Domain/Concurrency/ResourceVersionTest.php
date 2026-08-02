<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Concurrency;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Concurrency\InvalidResourceVersion;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;

final class ResourceVersionTest extends TestCase
{
    #[Test]
    public function it_creates_a_positive_resource_version_and_increments_it(): void
    {
        $version = ResourceVersion::fromInt(1);
        $next = $version->next();

        self::assertSame(1, $version->value());
        self::assertSame(2, $next->value());
    }

    #[Test]
    public function equality_requires_the_same_version_value(): void
    {
        $first = ResourceVersion::fromInt(5);
        $same = ResourceVersion::fromInt(5);
        $different = ResourceVersion::fromInt(6);

        self::assertTrue($first->equals($same));
        self::assertFalse($first->equals($different));
    }

    #[Test]
    public function it_rejects_non_positive_versions(): void
    {
        $this->expectException(InvalidResourceVersion::class);

        ResourceVersion::fromInt(0);
    }
}
