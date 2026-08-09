<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Command;

use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;

final readonly class UpdateRolePermissionsCommand
{
    /**
     * @param  list<PermissionCode>  $grants
     * @param  list<PermissionCode>  $revocations
     */
    public function __construct(
        private RoleId $roleId,
        private array $grants,
        private array $revocations,
    ) {}

    public function roleId(): RoleId
    {
        return $this->roleId;
    }

    /**
     * @return list<PermissionCode>
     */
    public function grants(): array
    {
        return $this->grants;
    }

    /**
     * @return list<PermissionCode>
     */
    public function revocations(): array
    {
        return $this->revocations;
    }
}
