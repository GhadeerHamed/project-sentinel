<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Exception;

use DomainException;

final class InvalidEventSource extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('Invalid Event Source field: %s.', $field));
    }
}
