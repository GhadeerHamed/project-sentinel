<?php

declare(strict_types=1);

namespace Sentinel\Application\Domain\Exception;

use DomainException;
use Sentinel\Application\Domain\ValueObject\ApplicationStatus;

final class InvalidApplicationTransition extends DomainException
{
    public static function fromTo(ApplicationStatus $from, ApplicationStatus $to): self
    {
        return new self(sprintf('Invalid application transition from %s to %s.', $from->value, $to->value));
    }
}
