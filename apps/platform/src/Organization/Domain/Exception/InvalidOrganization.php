<?php

declare(strict_types=1);

namespace Sentinel\Organization\Domain\Exception;

use DomainException;

final class InvalidOrganization extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf(
            'Invalid organization field: %s.',
            $field,
        ));
    }
}
