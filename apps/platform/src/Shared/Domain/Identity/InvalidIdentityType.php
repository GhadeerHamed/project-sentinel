<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Identity;

use DomainException;

final class InvalidIdentityType extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self(sprintf('Invalid identity type "%s".', $value));
    }
}
