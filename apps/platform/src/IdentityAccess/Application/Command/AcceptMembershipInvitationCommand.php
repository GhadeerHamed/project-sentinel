<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Command;

use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class AcceptMembershipInvitationCommand
{
    public function __construct(public OrganizationId $organizationId, public MembershipId $membershipId, public IdentityId $identityId, public ExpectedVersion $expectedVersion, public string $correlationId) {}
}
