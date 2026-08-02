<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\ValueObject;

use Sentinel\IdentityAccess\Domain\Exception\InvalidIdentityAccessState;

enum IdentityStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REVOKED = 'revoked';
    case ARCHIVED = 'archived';

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
