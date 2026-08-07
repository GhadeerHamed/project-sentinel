<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Command;

use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final readonly class SuspendMembershipCommand
{
    public function __construct(private MembershipId $membershipId, private ExpectedVersion $expectedVersion) {}

    public function membershipId(): MembershipId
    {
        return $this->membershipId;
    }

    public function expectedVersion(): ExpectedVersion
    {
        return $this->expectedVersion;
    }
}
