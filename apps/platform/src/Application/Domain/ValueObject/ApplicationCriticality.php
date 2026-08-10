<?php

declare(strict_types=1);

namespace Sentinel\Application\Domain\ValueObject;

use Sentinel\Application\Domain\Exception\InvalidApplication;

enum ApplicationCriticality: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? throw InvalidApplication::forField('criticality');
    }
}
