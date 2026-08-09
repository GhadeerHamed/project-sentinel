<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Authorization;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\Exception\AdministrativeAccessInvariantViolation;
use Sentinel\IdentityAccess\Domain\Repository\AdministrativeAccessRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class AdministrativeAccessInvariant
{
    public function __construct(
        private AdministrativeAccessRepository $administrativeAccess,
    ) {}

    public function lockOrganizationMutation(OrganizationId $organizationId): void
    {
        $this->administrativeAccess->lockOrganizationForAdministrativeAccessMutation($organizationId);
    }

    public function assertRetained(OrganizationId $organizationId, DateTimeImmutable $at): void
    {
        if (! $this->administrativeAccess->hasActiveHumanIdentityWithEffectivePermission(
            $organizationId,
            CorePermissions::manageOrganizationMembers(),
            $at,
        )) {
            throw AdministrativeAccessInvariantViolation::forOrganization($organizationId);
        }
    }
}
