<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

use DomainException;

final class InvalidIdempotencyRequest extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('Invalid idempotency field: %s.', $field));
    }
}
