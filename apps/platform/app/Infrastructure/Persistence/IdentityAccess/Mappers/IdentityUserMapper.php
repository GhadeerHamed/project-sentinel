<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Mappers;

use App\Infrastructure\Persistence\IdentityAccess\Records\IdentityUserRecord;
use Sentinel\IdentityAccess\Domain\Entity\User;

final class IdentityUserMapper
{
    public static function toDomain(IdentityUserRecord $record): User
    {
        return User::fromValues(
            identityId: $record->identity_id,
            email: $record->email,
            displayName: $record->display_name,
            status: $record->status,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
            version: $record->version,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toPersistence(User $user): array
    {
        return [
            'identity_id' => $user->identityId()->value(),
            'email' => $user->email(),
            'display_name' => $user->displayName(),
            'status' => $user->status()->value,
            'created_at' => $user->createdAt(),
            'updated_at' => $user->updatedAt(),
            'version' => $user->version()->value(),
        ];
    }
}
