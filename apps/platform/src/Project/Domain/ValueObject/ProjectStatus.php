<?php

declare(strict_types=1);

namespace Sentinel\Project\Domain\ValueObject;

use Sentinel\Project\Domain\Exception\InvalidProject;

enum ProjectStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? throw InvalidProject::forField('project_status');
    }
}
