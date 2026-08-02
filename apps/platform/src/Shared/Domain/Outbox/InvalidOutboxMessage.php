<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Outbox;

use DomainException;

final class InvalidOutboxMessage extends DomainException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('Invalid outbox message field "%s".', $field));
    }
}
