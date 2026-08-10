<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\ValueObject;

use Sentinel\Event\Domain\Exception\InvalidEventSource;

enum EventSourceStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? throw InvalidEventSource::forField('status');
    }
}
