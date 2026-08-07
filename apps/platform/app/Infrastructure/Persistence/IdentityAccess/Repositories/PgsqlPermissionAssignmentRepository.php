<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Sentinel\IdentityAccess\Domain\Repository\PermissionAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlPermissionAssignmentRepository implements PermissionAssignmentRepository
{
    public function assignToRole(OrganizationId $organizationId, RoleId $roleId, PermissionCode $permissionCode, IdentityId $grantedBy, DateTimeImmutable $grantedAt): void
    {
        $permissionId = DB::table('permissions')->where('code', $permissionCode->value())->value('id');
        if (! is_string($permissionId)) {
            return;
        }
        DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId->value(), 'permission_id' => $permissionId], ['granted_at' => $grantedAt, 'granted_by_identity_id' => $grantedBy->value()]);
    }

    public function revokeFromRole(OrganizationId $organizationId, RoleId $roleId, PermissionCode $permissionCode, IdentityId $revokedBy, DateTimeImmutable $revokedAt): void
    {
        DB::table('role_permissions')->where('role_id', $roleId->value())->whereIn('permission_id', DB::table('permissions')->select('id')->where('code', $permissionCode->value()))->delete();
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
            ->distinct()->pluck('permissions.code')->map(static fn (string $code): PermissionCode => PermissionCode::fromString($code))->all();
    }
}
