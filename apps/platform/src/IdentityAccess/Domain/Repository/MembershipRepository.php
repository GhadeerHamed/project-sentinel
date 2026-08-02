<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Repository;

use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface MembershipRepository
{
    /**
     * @throws MembershipNotFound
     */
    public function getById(MembershipId $membershipId, OrganizationId $organizationId): Membership;

    /**
     * @throws MembershipNotFound
     */
    public function getByIdentity(OrganizationId $organizationId, IdentityId $identityId): Membership;

    /**
     * Persist with optimistic concurrency against the aggregate version expected by the caller.
     */
    public function save(Membership $membership, ExpectedVersion $expectedVersion): void;
}
