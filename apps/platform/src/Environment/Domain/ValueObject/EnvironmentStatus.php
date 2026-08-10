<?php

declare(strict_types=1);

namespace Sentinel\Environment\Domain\ValueObject;

use Sentinel\Environment\Domain\Exception\InvalidEnvironment;

enum EnvironmentStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? throw InvalidEnvironment::forField('environment_status');
    }
}
