<?php

declare(strict_types=1);

namespace Sentinel\Environment\Domain\Exception;

use DomainException;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class EnvironmentNotFound extends DomainException
{
    public static function forId(EnvironmentId $environmentId, OrganizationId $organizationId, ApplicationId $applicationId): self
    {
        return new self(sprintf(
            'Environment %s was not found in application %s in organization %s.',
            $environmentId->value(),
            $applicationId->value(),
            $organizationId->value(),
        ));
    }
}
