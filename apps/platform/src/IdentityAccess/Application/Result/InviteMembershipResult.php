<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Result;

use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipStatus;

final readonly class InviteMembershipResult
{
    public function __construct(private MembershipId $membershipId, private MembershipStatus $status) {}

    public function membershipId(): MembershipId
    {
        return $this->membershipId;
    }

    public function status(): MembershipStatus
    {
        return $this->status;
    }
}
