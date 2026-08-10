<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\ValueObject;

use Sentinel\Event\Domain\Exception\InvalidEvent;

enum EventSeverity: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case INFORMATIONAL = 'informational';
    case UNKNOWN = 'unknown';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw InvalidEvent::forField('severity');
    }
}
