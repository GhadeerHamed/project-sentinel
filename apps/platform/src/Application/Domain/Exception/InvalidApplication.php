<?php

declare(strict_types=1);

namespace Sentinel\Application\Domain\Exception;

use DomainException;

final class InvalidApplication extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('Invalid application field: %s.', $field));
    }
}
