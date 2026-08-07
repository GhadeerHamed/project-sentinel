<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Exception;

use DomainException;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;

final class PermissionNotFound extends DomainException
{
    public static function forCode(PermissionCode $permissionCode): self
    {
        return new self(sprintf('Permission %s was not found.', $permissionCode->value()));
    }
}
