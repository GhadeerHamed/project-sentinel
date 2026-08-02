<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Repository;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\Entity\RoleAssignment;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface RoleAssignmentRepository
{
    public function assign(RoleAssignment $assignment): void;

    public function revoke(
        RoleAssignmentId $assignmentId,
        OrganizationId $organizationId,
        IdentityId $revokedBy,
        DateTimeImmutable $revokedAt,
    ): void;

    /**
     * @return list<RoleAssignment>
     */
    public function activeForIdentity(
        OrganizationId $organizationId,
        IdentityId $identityId,
        DateTimeImmutable $at,
    ): array;
}
