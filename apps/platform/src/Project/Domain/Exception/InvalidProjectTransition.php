<?php

declare(strict_types=1);

namespace Sentinel\Project\Domain\Exception;

use DomainException;
use Sentinel\Project\Domain\ValueObject\ProjectStatus;

final class InvalidProjectTransition extends DomainException
{
    public static function fromTo(ProjectStatus $from, ProjectStatus $to): self
    {
        return new self(sprintf(
            'Invalid project transition from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}
