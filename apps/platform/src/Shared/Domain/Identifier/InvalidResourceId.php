<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Identifier;

use DomainException;

final class InvalidResourceId extends DomainException
{
    public static function forValue(string $value, string $type): self
    {
        return new self(sprintf(
            'Invalid UUIDv7 value "%s" for %s.',
            $value,
            $type,
        ));
    }
}
