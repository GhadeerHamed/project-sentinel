<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Exception;

use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Shared\Domain\Error\DomainError;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class EventNotFound extends DomainError
{
    public static function forId(EventId $eventId, OrganizationId $organizationId): self
    {
        return new self(
            'The Event is not available.',
            ErrorCategory::NOT_FOUND,
            [
                'organization_id' => $organizationId->value(),
                'event_id' => $eventId->value(),
            ],
        );
    }
}
