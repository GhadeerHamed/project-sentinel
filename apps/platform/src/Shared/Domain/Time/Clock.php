<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Time;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
