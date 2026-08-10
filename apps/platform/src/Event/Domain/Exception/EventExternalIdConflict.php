<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Exception;

use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Error\DomainError;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class EventExternalIdConflict extends DomainError
{
    public static function forExternalEventId(
        OrganizationId $organizationId,
        EventSourceId $eventSourceId,
        string $externalEventId,
    ): self {
        return new self(
            'The external Event identifier was already used with different Event data.',
            ErrorCategory::CONFLICT,
            [
                'organization_id' => $organizationId->value(),
                'event_source_id' => $eventSourceId->value(),
                'external_event_id' => $externalEventId,
            ],
        );
    }
}
