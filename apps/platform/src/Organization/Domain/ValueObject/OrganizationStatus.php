<?php

declare(strict_types=1);

namespace Sentinel\Organization\Domain\ValueObject;

use Sentinel\Organization\Domain\Exception\InvalidOrganization;

enum OrganizationStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case ARCHIVED = 'archived';

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return self::tryFrom($normalized) ?? throw InvalidOrganization::forField('organization_status');
    }
}
