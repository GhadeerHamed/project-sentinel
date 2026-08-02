<?php

declare(strict_types=1);

namespace App\Infrastructure\Identifier;

use Illuminate\Support\Str;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;

final class LaravelUuidV7Generator implements UuidV7Generator
{
    public function generate(): string
    {
        return Str::uuid7()->toString();
    }
}
