<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Mappers;

use App\Infrastructure\Persistence\IdentityAccess\Records\RoleAssignmentRecord;
use Sentinel\IdentityAccess\Domain\Entity\RoleAssignment;

final class RoleAssignmentMapper
{
    public static function toDomain(RoleAssignmentRecord $record): RoleAssignment
    {
        $assignment = RoleAssignment::fromValues(
            id: $record->id,
            organizationId: $record->organization_id,
            identityId: $record->identity_id,
            roleId: $record->role_id,
            roleOrganizationId: $record->organization_id,
            assignedByIdentityId: $record->assigned_by_identity_id,
            assignedAt: $record->assigned_at,
            expiresAt: $record->expires_at,
        );

        if ($record->revoked_at !== null && $record->revoked_by_identity_id !== null) {
            $assignment = $assignment->revoke(
                revokedAt: $record->revoked_at,
                revokedByIdentityId: $record->revoked_by_identity_id,
            );
        }

        return $assignment;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(RoleAssignment $assignment): array
    {
        return [
            'id' => $assignment->id()->value(),
            'organization_id' => $assignment->organizationId()->value(),
            'identity_id' => $assignment->identityId()->value(),
            'role_id' => $assignment->roleId()->value(),
            'assigned_by_identity_id' => $assignment->assignedByIdentityId()->value(),
            'assigned_at' => $assignment->assignedAt(),
            'expires_at' => $assignment->expiresAt(),
            'revoked_at' => $assignment->revokedAt(),
            'revoked_by_identity_id' => $assignment->revokedByIdentityId()?->value(),
            'created_at' => $assignment->assignedAt(),
            'updated_at' => $assignment->revokedAt() ?? $assignment->assignedAt(),
        ];
    }
}
