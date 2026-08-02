<?php

declare(strict_types=1);

namespace Sentinel\Organization\Domain\Exception;

use DomainException;
use Sentinel\Organization\Domain\ValueObject\OrganizationStatus;

final class InvalidOrganizationTransition extends DomainException
{
    public static function fromTo(OrganizationStatus $from, OrganizationStatus $to): self
    {
        return new self(sprintf(
            'Invalid organization transition from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}
