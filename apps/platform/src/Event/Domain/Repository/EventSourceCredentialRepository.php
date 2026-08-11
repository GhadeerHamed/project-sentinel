<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Repository;

use Sentinel\Event\Domain\Entity\EventSourceCredential;
use Sentinel\Event\Domain\ValueObject\EventSourceCredentialId;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface EventSourceCredentialRepository
{
    public function findById(EventSourceCredentialId $credentialId, OrganizationId $organizationId): ?EventSourceCredential;

    public function save(EventSourceCredential $credential): void;

    public function revoke(
        EventSourceCredentialId $credentialId,
        EventSourceId $eventSourceId,
        OrganizationId $organizationId,
        \DateTimeImmutable $revokedAt,
    ): bool;
}
