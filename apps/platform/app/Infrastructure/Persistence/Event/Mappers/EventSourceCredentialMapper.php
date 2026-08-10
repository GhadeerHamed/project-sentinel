<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Mappers;

use App\Infrastructure\Persistence\Event\Records\EventSourceCredentialRecord;
use Sentinel\Event\Domain\Entity\EventSourceCredential;

final class EventSourceCredentialMapper
{
    public static function toDomain(EventSourceCredentialRecord $record): EventSourceCredential
    {
        return EventSourceCredential::fromValues(
            id: $record->id,
            organizationId: $record->organization_id,
            eventSourceId: $record->event_source_id,
            secretHash: $record->secret_hash,
            status: $record->status,
            createdAt: $record->created_at,
            revokedAt: $record->revoked_at,
            expiresAt: $record->expires_at,
        );
    }

    /** @return array<string, mixed> */
    public static function toPersistence(EventSourceCredential $credential): array
    {
        return [
            'id' => $credential->id()->value(),
            'organization_id' => $credential->organizationId()->value(),
            'event_source_id' => $credential->eventSourceId()->value(),
            'secret_hash' => $credential->secretHash(),
            'status' => $credential->status()->value,
            'created_at' => $credential->createdAt(),
            'revoked_at' => $credential->revokedAt(),
            'expires_at' => $credential->expiresAt(),
        ];
    }
}
