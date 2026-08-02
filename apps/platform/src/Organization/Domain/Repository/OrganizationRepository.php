<?php

declare(strict_types=1);

namespace Sentinel\Organization\Domain\Repository;

use Sentinel\Organization\Domain\Entity\Organization;
use Sentinel\Organization\Domain\Exception\OrganizationNotFound;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface OrganizationRepository
{
    /**
     * @throws OrganizationNotFound
     */
    public function getById(OrganizationId $organizationId): Organization;

    /**
     * Persist with optimistic concurrency against the aggregate version expected by the caller.
     */
    public function save(Organization $organization, ExpectedVersion $expectedVersion): void;
}
