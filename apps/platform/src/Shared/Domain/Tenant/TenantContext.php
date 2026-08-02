<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Tenant;

use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identity\IdentityReference;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class TenantContext
{
    private function __construct(
        private OrganizationId $organizationId,
        private IdentityReference $actingIdentity,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
    ) {}

    public static function fromValues(
        string $organizationId,
        string $identityId,
        string $identityType,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        return new self(
            organizationId: OrganizationId::fromString($organizationId),
            actingIdentity: IdentityReference::fromString($identityId, $identityType),
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
        );
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function actingIdentity(): IdentityReference
    {
        return $this->actingIdentity;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->causationId;
    }

    public function equals(self $other): bool
    {
        $causationIsEqual = ($this->causationId === null && $other->causationId === null)
            || ($this->causationId !== null
                && $other->causationId !== null
                && $this->causationId->equals($other->causationId));

        return $this->organizationId->equals($other->organizationId)
            && $this->actingIdentity->equals($other->actingIdentity)
            && $this->correlationId->equals($other->correlationId)
            && $causationIsEqual;
    }
}
