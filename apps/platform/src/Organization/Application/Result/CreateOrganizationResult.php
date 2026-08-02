<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Result;

use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class CreateOrganizationResult
{
    private function __construct(
        private OrganizationId $organizationId,
        private MembershipId $creatorMembershipId,
    ) {}

    public static function fromValues(string $organizationId, string $creatorMembershipId): self
    {
        return new self(
            organizationId: OrganizationId::fromString($organizationId),
            creatorMembershipId: MembershipId::fromString($creatorMembershipId),
        );
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function creatorMembershipId(): MembershipId
    {
        return $this->creatorMembershipId;
    }
}
