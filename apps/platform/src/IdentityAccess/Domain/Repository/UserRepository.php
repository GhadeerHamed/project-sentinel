<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Repository;

use Sentinel\IdentityAccess\Domain\Entity\User;
use Sentinel\IdentityAccess\Domain\Exception\UserNotFound;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Identity\IdentityId;

interface UserRepository
{
    /**
     * @throws UserNotFound
     */
    public function getByIdentityId(IdentityId $identityId): User;

    /**
     * Persist with optimistic concurrency against the aggregate version expected by the caller.
     */
    public function save(User $user, ExpectedVersion $expectedVersion): void;
}
