<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Concurrency;

use DomainException;

final class InvalidResourceVersion extends DomainException
{
    public static function forValue(int $value): self
    {
        return new self(sprintf(
            'Invalid resource version "%d". Version must be a positive integer.',
            $value,
        ));
    }
}
