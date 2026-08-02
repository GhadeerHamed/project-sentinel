<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipStatus;

final class InvalidMembershipTransition extends DomainException
{
    public static function fromTo(MembershipStatus $from, MembershipStatus $to): self
    {
        return new self(sprintf(
            'Invalid membership transition from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}
