<?php

declare(strict_types=1);

namespace Sentinel\Application\Domain\Exception;

use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\DomainError;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class ApplicationSlugConflict extends DomainError
{
    public static function forSlug(OrganizationId $organizationId, ProjectId $projectId, string $slug): self
    {
        return new self(
            'The application slug is already in use by this project.',
            ErrorCategory::CONFLICT,
            [
                'organization_id' => $organizationId->value(),
                'project_id' => $projectId->value(),
                'slug' => $slug,
            ],
        );
    }
}
