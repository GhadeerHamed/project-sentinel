<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Sentinel\IdentityAccess\Domain\Repository\AdministrativeAccessRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlAdministrativeAccessRepository implements AdministrativeAccessRepository
{
    public function lockOrganizationForAdministrativeAccessMutation(OrganizationId $organizationId): void
    {
        DB::table('organizations')
            ->where('id', $organizationId->value())
            ->lockForUpdate()
            ->first();
    }

    public function hasActiveHumanIdentityWithEffectivePermission(OrganizationId $organizationId, PermissionCode $permissionCode, DateTimeImmutable $at): bool
    {
        return DB::table('memberships')
            ->join('identity_users', 'identity_users.identity_id', '=', 'memberships.user_identity_id')
            ->join('role_assignments', function ($join): void {
                $join
                    ->on('role_assignments.organization_id', '=', 'memberships.organization_id')
                    ->on('role_assignments.identity_id', '=', 'memberships.user_identity_id');
            })
            ->join('organization_roles', function ($join): void {
                $join
                    ->on('organization_roles.id', '=', 'role_assignments.role_id')
                    ->on('organization_roles.organization_id', '=', 'role_assignments.organization_id');
            })
            ->join('role_permissions', 'role_permissions.role_id', '=', 'organization_roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('memberships.organization_id', $organizationId->value())
            ->where('memberships.status', 'active')
            ->where('identity_users.status', 'active')
            ->where('permissions.code', $permissionCode->value())
            ->where(function ($query) use ($at): void {
                $query->whereNull('role_assignments.revoked_at')
                    ->orWhere('role_assignments.revoked_at', '>', $at);
            })
            ->where(function ($query) use ($at): void {
                $query->whereNull('role_assignments.expires_at')
                    ->orWhere('role_assignments.expires_at', '>', $at);
            })
            ->where(function ($query) use ($at): void {
                $query->whereNull('role_permissions.revoked_at')
                    ->orWhere('role_permissions.revoked_at', '>', $at);
            })
            ->exists();
    }
}
