<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Handler;

use Sentinel\Event\Application\Policy\EventListingPolicy;
use Sentinel\Event\Application\Query\EventListQuery;
use Sentinel\Event\Application\Query\ListEventsQuery;
use Sentinel\Event\Application\ReadModel\EventListResult;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ListEventsHandler
{
    public function __construct(private EventListQuery $events, private EventListingPolicy $policy, private TenantContext $tenantContext) {}

    public function handle(ListEventsQuery $query): EventListResult
    {
        if (! $this->policy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to list Events.');
        }

        return $this->events->list($this->tenantContext->organizationId(), $query);
    }
}
