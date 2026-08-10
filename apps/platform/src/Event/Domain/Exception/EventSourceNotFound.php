<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Exception;

use DomainException;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class EventSourceNotFound extends DomainException
{
    public static function forId(EventSourceId $eventSourceId, OrganizationId $organizationId): self
    {
        return new self(sprintf(
            'Event Source %s was not found in organization %s.',
            $eventSourceId->value(),
            $organizationId->value(),
        ));
    }
}
