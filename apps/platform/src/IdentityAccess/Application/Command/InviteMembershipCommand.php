<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Command;

use Sentinel\Shared\Domain\Identity\IdentityId;

final readonly class InviteMembershipCommand
{
    public function __construct(private IdentityId $memberIdentityId) {}

    public function memberIdentityId(): IdentityId
    {
        return $this->memberIdentityId;
    }
}
