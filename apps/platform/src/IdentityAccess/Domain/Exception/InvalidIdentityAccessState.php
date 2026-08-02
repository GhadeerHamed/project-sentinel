<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;

final class InvalidIdentityAccessState extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf(
            'Invalid identity and access field: %s.',
            $field,
        ));
    }
}
