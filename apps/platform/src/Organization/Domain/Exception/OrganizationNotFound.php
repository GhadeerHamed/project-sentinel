<?php

declare(strict_types=1);

namespace Sentinel\Organization\Domain\Exception;

use DomainException;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class OrganizationNotFound extends DomainException
{
    public static function forId(OrganizationId $organizationId): self
    {
        return new self(sprintf(
            'Organization %s was not found.',
            $organizationId->value(),
        ));
    }
}
