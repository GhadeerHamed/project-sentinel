<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Repositories;

use App\Infrastructure\Persistence\Event\Mappers\EventSourceCredentialMapper;
use App\Infrastructure\Persistence\Event\Records\EventSourceCredentialRecord;
use Sentinel\Event\Domain\Entity\EventSourceCredential;
use Sentinel\Event\Domain\Repository\EventSourceCredentialRepository;
use Sentinel\Event\Domain\ValueObject\EventSourceCredentialId;
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
}
