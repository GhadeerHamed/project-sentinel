<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Handler;

use Sentinel\Organization\Application\ReadModel\AccessibleOrganization;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Identity\IdentityReference;

final readonly class ListAccessibleOrganizationsHandler
{
    public function __construct(private OrganizationRepository $organizations) {}

    /** @return list<AccessibleOrganization> */
    public function handle(IdentityReference $identity): array
    {
        return $this->organizations->accessibleTo(IdentityId::fromString($identity->identityId()));
    }
}
