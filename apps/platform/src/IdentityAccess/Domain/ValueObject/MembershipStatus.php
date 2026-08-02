<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\ValueObject;

use Sentinel\IdentityAccess\Domain\Exception\InvalidIdentityAccessState;

enum MembershipStatus: string
{
    case INVITED = 'invited';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REMOVED = 'removed';
    case DECLINED = 'declined';
    case EXPIRED = 'expired';

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return self::tryFrom($normalized) ?? throw InvalidIdentityAccessState::forField('membership_status');
    }

    public function grantsAccess(): bool
    {
        return $this === self::ACTIVE;
    }
}
