<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Exception;

use DomainException;

final class InvalidEventSourceCredential extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('Invalid Event Source credential field: %s.', $field));
    }
}
