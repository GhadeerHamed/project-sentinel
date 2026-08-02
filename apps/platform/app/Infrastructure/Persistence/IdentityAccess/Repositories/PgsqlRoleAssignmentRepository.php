<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Repositories;

use App\Infrastructure\Persistence\IdentityAccess\Mappers\RoleAssignmentMapper;
use App\Infrastructure\Persistence\IdentityAccess\Records\OrganizationRoleRecord;
use App\Infrastructure\Persistence\IdentityAccess\Records\RoleAssignmentRecord;
use DateTimeImmutable;
use DomainException;
use Sentinel\IdentityAccess\Domain\Entity\RoleAssignment;
use Sentinel\IdentityAccess\Domain\Exception\CrossOrganizationAssignment;
use Sentinel\IdentityAccess\Domain\Exception\InvalidRoleAssignmentTransition;
use Sentinel\IdentityAccess\Domain\Repository\RoleAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlRoleAssignmentRepository implements RoleAssignmentRepository
{
    public function assign(RoleAssignment $assignment): void
    {
        if (! $this->roleBelongsToOrganization($assignment->roleId()->value(), $assignment->organizationId()->value())) {
            throw CrossOrganizationAssignment::forRoleAssignment();
        }

        RoleAssignmentRecord::query()->create(RoleAssignmentMapper::toPersistence($assignment));
    }

    public function revoke(
        RoleAssignmentId $assignmentId,
        OrganizationId $organizationId,
        IdentityId $revokedBy,
        DateTimeImmutable $revokedAt,
    ): void {
        /** @var RoleAssignmentRecord|null $record */
        $record = RoleAssignmentRecord::query()
            ->whereKey($assignmentId->value())
            ->where('organization_id', $organizationId->value())
            ->first();

        if ($record === null) {
            throw new DomainException(sprintf(
                'Role assignment %s was not found in organization %s.',
                $assignmentId->value(),
                $organizationId->value(),
            ));
        }

        if ($record->revoked_at !== null) {
            throw InvalidRoleAssignmentTransition::alreadyRevoked();
        }

        $record->revoked_at = $revokedAt;
        $record->revoked_by_identity_id = $revokedBy->value();
        $record->updated_at = $revokedAt;
        $record->save();
    }

    public function activeForIdentity(
        OrganizationId $organizationId,
        IdentityId $identityId,
        DateTimeImmutable $at,
    ): array {
        $records = RoleAssignmentRecord::query()
            ->select('role_assignments.*')
            ->join('organization_roles as roles', function ($join): void {
                $join
                    ->on('roles.id', '=', 'role_assignments.role_id')
                    ->on('roles.organization_id', '=', 'role_assignments.organization_id');
            })
            ->where('role_assignments.organization_id', $organizationId->value())
            ->where('role_assignments.identity_id', $identityId->value())
            ->where(function ($query) use ($at): void {
                $query->whereNull('role_assignments.revoked_at')
                    ->orWhere('role_assignments.revoked_at', '>', $at);
            })
            ->where(function ($query) use ($at): void {
                $query->whereNull('role_assignments.expires_at')
                    ->orWhere('role_assignments.expires_at', '>', $at);
            })
            ->orderBy('role_assignments.assigned_at')
            ->get();

        return $records
            ->map(static fn (RoleAssignmentRecord $record): RoleAssignment => RoleAssignmentMapper::toDomain($record))
            ->all();
    }

    private function roleBelongsToOrganization(string $roleId, string $organizationId): bool
    {
        return OrganizationRoleRecord::query()
            ->whereKey($roleId)
            ->where('organization_id', $organizationId)
            ->exists();
    }
}
