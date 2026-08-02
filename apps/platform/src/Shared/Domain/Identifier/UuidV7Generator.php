<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Identifier;

interface UuidV7Generator
{
    public function generate(): string;
}
