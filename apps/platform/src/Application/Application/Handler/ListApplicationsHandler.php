<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Handler;

use Sentinel\Application\Application\Policy\ApplicationListingPolicy;
use Sentinel\Application\Application\Query\ApplicationListQuery;
use Sentinel\Application\Application\Query\ListApplicationsQuery;
use Sentinel\Application\Application\ReadModel\ApplicationListResult;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ListApplicationsHandler
{
    public function __construct(
        private ApplicationListQuery $applications,
        private ProjectLookupQuery $projects,
        private ApplicationListingPolicy $applicationListingPolicy,
        private TenantContext $tenantContext,
    ) {}

    public function handle(ProjectId $projectId, ListApplicationsQuery $query): ApplicationListResult
    {
        if (! $this->applicationListingPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to list applications.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        $this->projects->get($this->tenantContext->organizationId(), $projectId);

        return $this->applications->list($this->tenantContext->organizationId(), $projectId, $query);
    }
}
