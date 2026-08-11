<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Handler;

use Sentinel\Event\Application\Policy\EventDetailVisibilityPolicy;
use Sentinel\Event\Application\Policy\EventRetrievalPolicy;
use Sentinel\Event\Application\Query\EventDetailQuery;
use Sentinel\Event\Application\ReadModel\EventDetail;
use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class GetEventHandler
{
    public function __construct(private EventDetailQuery $events, private EventRetrievalPolicy $policy, private EventDetailVisibilityPolicy $visibility, private TenantContext $tenantContext) {}

    public function handle(EventId $eventId): EventDetail
    {
        if (! $this->policy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to retrieve Events.');
        }
        $event = $this->events->get($this->tenantContext->organizationId(), $eventId);
        if ($event === null) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::NOT_FOUND, 'The Event is not available.');
        }
        if (! $this->visibility->permitsPayloadAndAttributes($this->tenantContext)) {
            return new EventDetail($event->id(), $event->attributes(), [], []);
        }

        return $event;
    }
}
