<?php

declare(strict_types=1);

namespace Sentinel\Project\Domain\Exception;

use DomainException;

final class InvalidProject extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('Invalid project field: %s.', $field));
    }
}
