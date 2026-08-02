<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Audit;

use DomainException;

final class InvalidAuditRecord extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf(
            'Invalid audit record field: %s.',
            $field,
        ));
    }
}
