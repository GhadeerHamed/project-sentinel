<?php

declare(strict_types=1);

namespace Sentinel\Environment\Domain\Exception;

use DomainException;

final class InvalidEnvironment extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('Invalid environment field: %s.', $field));
    }
}
