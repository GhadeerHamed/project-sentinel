<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\ValueObject;

use Sentinel\Event\Domain\Exception\InvalidEventSourceCredential;

enum EventSourceCredentialStatus: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
    case DISABLED = 'disabled';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? throw InvalidEventSourceCredential::forField('status');
    }
}
