<?php

declare(strict_types=1);

namespace Sentinel\Environment\Domain\Exception;

use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Shared\Domain\Error\DomainError;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class EnvironmentSlugConflict extends DomainError
{
    public static function forSlug(OrganizationId $organizationId, ApplicationId $applicationId, string $slug): self
    {
        return new self(
            'The environment slug is already in use by this application.',
            ErrorCategory::CONFLICT,
            [
                'organization_id' => $organizationId->value(),
                'application_id' => $applicationId->value(),
                'slug' => $slug,
            ],
        );
    }
}
