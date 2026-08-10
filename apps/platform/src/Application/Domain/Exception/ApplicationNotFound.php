<?php

declare(strict_types=1);

namespace Sentinel\Application\Domain\Exception;

use DomainException;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class ApplicationNotFound extends DomainException
{
    public static function forId(ApplicationId $applicationId, OrganizationId $organizationId, ProjectId $projectId): self
    {
        return new self(sprintf(
            'Application %s was not found in project %s in organization %s.',
            $applicationId->value(),
            $projectId->value(),
            $organizationId->value(),
        ));
    }
}
