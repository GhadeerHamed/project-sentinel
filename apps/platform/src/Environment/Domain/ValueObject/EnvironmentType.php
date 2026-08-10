<?php

declare(strict_types=1);

namespace Sentinel\Environment\Domain\ValueObject;

use Sentinel\Environment\Domain\Exception\InvalidEnvironment;

enum EnvironmentType: string
{
    case PRODUCTION = 'production';
    case STAGING = 'staging';
    case TESTING = 'testing';
    case DEVELOPMENT = 'development';
    case SANDBOX = 'sandbox';
    case OTHER = 'other';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? throw InvalidEnvironment::forField('environment_type');
    }
}
