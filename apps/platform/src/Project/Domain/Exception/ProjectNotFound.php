<?php

declare(strict_types=1);

namespace Sentinel\Project\Domain\Exception;

use DomainException;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class ProjectNotFound extends DomainException
{
    public static function forId(ProjectId $projectId, OrganizationId $organizationId): self
    {
        return new self(sprintf(
            'Project %s was not found in organization %s.',
            $projectId->value(),
            $organizationId->value(),
        ));
    }
}
