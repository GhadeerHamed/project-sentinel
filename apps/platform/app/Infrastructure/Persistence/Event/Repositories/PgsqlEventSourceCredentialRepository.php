<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Repositories;

use App\Infrastructure\Persistence\Event\Mappers\EventSourceCredentialMapper;
use App\Infrastructure\Persistence\Event\Records\EventSourceCredentialRecord;
use Sentinel\Event\Domain\Entity\EventSourceCredential;
use Sentinel\Event\Domain\Repository\EventSourceCredentialRepository;
use Sentinel\Event\Domain\ValueObject\EventSourceCredentialId;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlEventSourceCredentialRepository implements EventSourceCredentialRepository
{
    public function findById(EventSourceCredentialId $credentialId, OrganizationId $organizationId): ?EventSourceCredential
    {
        $record = EventSourceCredentialRecord::query()
            ->where('id', $credentialId->value())
            ->where('organization_id', $organizationId->value())
            ->first();

        return $record === null ? null : EventSourceCredentialMapper::toDomain($record);
    }

    public function save(EventSourceCredential $credential): void
    {
        EventSourceCredentialRecord::query()->create(EventSourceCredentialMapper::toPersistence($credential));
    }

    public function revoke(EventSourceCredentialId $credentialId, EventSourceId $eventSourceId, OrganizationId $organizationId, \DateTimeImmutable $revokedAt): bool
    {
        return EventSourceCredentialRecord::query()
            ->where('id', $credentialId->value())
            ->where('event_source_id', $eventSourceId->value())
            ->where('organization_id', $organizationId->value())
            ->where('status', 'active')
            ->update(['status' => 'revoked', 'revoked_at' => $revokedAt]) === 1;
    }
}
