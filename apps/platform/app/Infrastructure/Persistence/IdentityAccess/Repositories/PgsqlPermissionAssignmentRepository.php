<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Sentinel\IdentityAccess\Domain\Exception\PermissionNotFound;
use Sentinel\IdentityAccess\Domain\Exception\RoleNotFound;
use Sentinel\IdentityAccess\Domain\Repository\PermissionAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlPermissionAssignmentRepository implements PermissionAssignmentRepository
{
    public function assignToRole(OrganizationId $organizationId, RoleId $roleId, PermissionCode $permissionCode, IdentityId $grantedBy, DateTimeImmutable $grantedAt): void
    {
        $this->assertRoleBelongsToOrganization($organizationId, $roleId);

        DB::table('role_permissions')->updateOrInsert(
            ['role_id' => $roleId->value(), 'permission_id' => $this->permissionId($permissionCode)],
            [
                'granted_at' => $grantedAt,
                'granted_by_identity_id' => $grantedBy->value(),
                'revoked_at' => null,
                'revoked_by_identity_id' => null,
            ],
        );
    }

    public function revokeFromRole(OrganizationId $organizationId, RoleId $roleId, PermissionCode $permissionCode, IdentityId $revokedBy, DateTimeImmutable $revokedAt): void
    {
        $this->assertRoleBelongsToOrganization($organizationId, $roleId);

        DB::table('role_permissions')
            ->where('role_id', $roleId->value())
            ->where('permission_id', $this->permissionId($permissionCode))
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $revokedAt,
                'revoked_by_identity_id' => $revokedBy->value(),
            ]);
    }

    public function permissionsForIdentity(OrganizationId $organizationId, IdentityId $identityId, DateTimeImmutable $at): array
    {
        return DB::table('role_assignments')
            ->join('organization_roles', function ($join): void {
                $join->on('organization_roles.id', '=', 'role_assignments.role_id')->on('organization_roles.organization_id', '=', 'role_assignments.organization_id');
            })
            ->join('role_permissions', 'role_permissions.role_id', '=', 'organization_roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_assignments.organization_id', $organizationId->value())
            ->where('role_assignments.identity_id', $identityId->value())
            ->where(function ($query) use ($at): void {
                $query->whereNull('role_assignments.revoked_at')->orWhere('role_assignments.revoked_at', '>', $at);
            })
            ->where(function ($query) use ($at): void {
                $query->whereNull('role_assignments.expires_at')->orWhere('role_assignments.expires_at', '>', $at);
            })
            ->where(function ($query) use ($at): void {
                $query->whereNull('role_permissions.revoked_at')->orWhere('role_permissions.revoked_at', '>', $at);
            })
            ->distinct()->pluck('permissions.code')->map(static fn (string $code): PermissionCode => PermissionCode::fromString($code))->all();
    }

    private function assertRoleBelongsToOrganization(OrganizationId $organizationId, RoleId $roleId): void
    {
        $roleExists = DB::table('organization_roles')
            ->where('id', $roleId->value())
            ->where('organization_id', $organizationId->value())
            ->exists();

        if (! $roleExists) {
            throw RoleNotFound::forId($roleId, $organizationId);
        }
    }

    private function permissionId(PermissionCode $permissionCode): string
    {
        $permissionId = DB::table('permissions')->where('code', $permissionCode->value())->value('id');

        if (! is_string($permissionId)) {
            throw PermissionNotFound::forCode($permissionCode);
        }

        return $permissionId;
    }
}
