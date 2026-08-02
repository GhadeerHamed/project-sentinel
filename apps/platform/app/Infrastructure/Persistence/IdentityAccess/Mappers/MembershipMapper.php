<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Mappers;

use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use Sentinel\IdentityAccess\Domain\Entity\Membership;

final class MembershipMapper
{
    public static function toDomain(MembershipRecord $record): Membership
    {
        return Membership::fromValues(
            id: $record->id,
            organizationId: $record->organization_id,
            userIdentityId: $record->user_identity_id,
            status: $record->status,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
            activatedAt: $record->activated_at,
            suspendedAt: $record->suspended_at,
            removedAt: $record->removed_at,
            version: $record->version,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(Membership $membership): array
    {
        return [
            'id' => $membership->id()->value(),
            'organization_id' => $membership->organizationId()->value(),
            'user_identity_id' => $membership->userIdentityId()->value(),
            'status' => $membership->status()->value,
            'activated_at' => $membership->activatedAt(),
            'suspended_at' => $membership->suspendedAt(),
            'removed_at' => $membership->removedAt(),
            'created_at' => $membership->createdAt(),
            'updated_at' => $membership->updatedAt(),
            'version' => $membership->version()->value(),
        ];
    }
}
