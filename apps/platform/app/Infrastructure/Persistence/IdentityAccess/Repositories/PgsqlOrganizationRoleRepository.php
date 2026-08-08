<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Repositories;

use App\Infrastructure\Persistence\IdentityAccess\Records\OrganizationRoleRecord;
use Sentinel\IdentityAccess\Domain\Entity\OrganizationRole;
use Sentinel\IdentityAccess\Domain\Exception\RoleNotFound;
use Sentinel\IdentityAccess\Domain\Repository\OrganizationRoleRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlOrganizationRoleRepository implements OrganizationRoleRepository
{
    public function getById(RoleId $roleId, OrganizationId $organizationId): OrganizationRole
    {
        /** @var OrganizationRoleRecord|null $record */
        $record = OrganizationRoleRecord::query()
            ->whereKey($roleId->value())
            ->where('organization_id', $organizationId->value())
            ->first();

        if ($record === null) {
            throw RoleNotFound::forId($roleId, $organizationId);
        }

        return OrganizationRole::fromValues($record->id, $record->organization_id, $record->role_key);
    }
}
