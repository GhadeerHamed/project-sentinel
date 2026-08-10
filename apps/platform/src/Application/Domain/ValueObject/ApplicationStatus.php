<?php

declare(strict_types=1);

namespace Sentinel\Application\Domain\ValueObject;

use Sentinel\Application\Domain\Exception\InvalidApplication;

enum ApplicationStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? throw InvalidApplication::forField('application_status');
    }
}
