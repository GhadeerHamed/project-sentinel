<?php

declare(strict_types=1);

namespace Sentinel\Environment\Domain\Exception;

use DomainException;
use Sentinel\Environment\Domain\ValueObject\EnvironmentStatus;

final class InvalidEnvironmentTransition extends DomainException
{
    public static function fromTo(EnvironmentStatus $from, EnvironmentStatus $to): self
    {
        return new self(sprintf(
            'Invalid environment transition from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}
