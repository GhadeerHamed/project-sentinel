<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Identifier;

use App\Infrastructure\Identifier\LaravelUuidV7Generator;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class UuidV7GeneratorBindingTest extends TestCase
{
    #[Test]
    public function it_resolves_the_laravel_uuid_v7_adapter(): void
    {
        $generator = $this->app->make(UuidV7Generator::class);

        self::assertInstanceOf(
            LaravelUuidV7Generator::class,
            $generator,
        );
    }
}
