<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Identifier;

use App\Infrastructure\Identifier\LaravelUuidV7Generator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Identifier\ResourceId;

final class LaravelUuidV7GeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_valid_uuid_v7_values(): void
    {
        $generator = new LaravelUuidV7Generator;

        $id = TestResourceId::fromString($generator->generate());

        self::assertSame(7, (int) $id->value()[14]);
    }

    #[Test]
    public function it_generates_unique_values(): void
    {
        $generator = new LaravelUuidV7Generator;

        self::assertNotSame(
            $generator->generate(),
            $generator->generate(),
        );
    }
}

final readonly class TestResourceId extends ResourceId {}
