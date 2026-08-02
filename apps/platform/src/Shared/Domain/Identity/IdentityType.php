<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Identity;

enum IdentityType: string
{
    case USER = 'user';
    case API_CLIENT = 'api_client';
    case SERVICE = 'service';
    case AGENT = 'agent';
    case PLUGIN = 'plugin';
    case PLATFORM = 'platform';

    public static function fromString(string $value): self
    {
        $normalized = strtolower($value);

        return self::tryFrom($normalized) ?? throw InvalidIdentityType::forValue($value);
    }
}
