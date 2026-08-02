<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;
use Sentinel\Shared\Domain\Identity\IdentityId;

final class UserNotFound extends DomainException
{
    public static function forIdentity(IdentityId $identityId): self
    {
        return new self(sprintf(
            'User with identity %s was not found.',
            $identityId->value(),
        ));
    }
}
