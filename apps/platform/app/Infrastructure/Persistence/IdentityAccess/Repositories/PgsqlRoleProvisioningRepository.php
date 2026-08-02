<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Repositories;

use App\Infrastructure\Persistence\IdentityAccess\Records\OrganizationRoleRecord;
use Illuminate\Support\Facades\DB;
use Sentinel\IdentityAccess\Domain\Repository\RoleProvisioningRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleKey;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class PgsqlRoleProvisioningRepository implements RoleProvisioningRepository
{
    public function __construct(
        private UuidV7Generator $uuidGenerator,
    ) {}

    public function provisionRole(OrganizationId $organizationId, RoleKey $roleKey): RoleId
    {
        $existing = OrganizationRoleRecord::query()
            ->where('organization_id', $organizationId->value())
            ->where('role_key', $roleKey->value())
            ->first();

        if ($existing !== null) {
            return RoleId::fromString($existing->id);
        }

        $now = new \DateTimeImmutable('now');
        $generatedRoleId = $this->uuidGenerator->generate();

        $inserted = DB::table('organization_roles')->insertOrIgnore([
            'id' => $generatedRoleId,
            'organization_id' => $organizationId->value(),
            'role_key' => $roleKey->value(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted === 1) {
            return RoleId::fromString($generatedRoleId);
        }

        $resolved = OrganizationRoleRecord::query()
            ->where('organization_id', $organizationId->value())
            ->where('role_key', $roleKey->value())
            ->first();

        if ($resolved === null) {
            throw new \RuntimeException('Role provisioning insert was ignored but no role could be resolved.');
        }

        return RoleId::fromString($resolved->id);
    }
}
