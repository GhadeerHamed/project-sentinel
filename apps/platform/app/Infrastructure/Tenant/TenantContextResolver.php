<?php

declare(strict_types=1);

namespace App\Infrastructure\Tenant;

use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Organization\Domain\ValueObject\OrganizationStatus;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Identity\IdentityReference;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class TenantContextResolver
{
    public function __construct(
        private OrganizationRepository $organizations,
        private MembershipRepository $memberships,
    ) {}

    public function resolve(
        IdentityReference $identity,
        string $requestedOrganizationId,
        string $correlationId,
    ): TenantContext {
        $organizationId = OrganizationId::fromString($requestedOrganizationId);
        $organization = $this->organizations->getById($organizationId);

        if (! $organization->id()->equals($organizationId)
            || $organization->status() !== OrganizationStatus::ACTIVE) {
            throw new TenantContextResolutionFailed;
        }

        $identityId = IdentityId::fromString($identity->identityId());
        $membership = $this->memberships->getByIdentity($organizationId, $identityId);

        if (! $membership->organizationId()->equals($organizationId)
            || ! $membership->userIdentityId()->equals($identityId)
            || ! $membership->grantsOrganizationAccess()) {
            throw new TenantContextResolutionFailed;
        }

        return TenantContext::fromValues(
            organizationId: $organizationId->value(),
            identityId: $identity->identityId(),
            identityType: $identity->identityType(),
            correlationId: $correlationId,
        );
    }
}
