<?php

declare(strict_types=1);

namespace Sentinel\Project\Domain\Exception;

use Sentinel\Shared\Domain\Error\DomainError;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class ProjectSlugConflict extends DomainError
{
    public static function forSlug(OrganizationId $organizationId, string $slug): self
    {
        return new self(
            'The project slug is already in use by this organization.',
            ErrorCategory::CONFLICT,
            [
                'organization_id' => $organizationId->value(),
                'slug' => $slug,
            ],
        );
    }
}
