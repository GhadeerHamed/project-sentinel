<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\ValueObject;

use Sentinel\IdentityAccess\Domain\Exception\InvalidIdentityAccessState;

enum IdentityStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DISABLED = 'disabled';
    case COMPROMISED = 'compromised';

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return self::tryFrom($normalized) ?? throw InvalidIdentityAccessState::forField('identity_status');
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
