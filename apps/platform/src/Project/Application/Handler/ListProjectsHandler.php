<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Handler;

use Sentinel\Project\Application\Policy\ProjectListingPolicy;
use Sentinel\Project\Application\Query\ListProjectsQuery;
use Sentinel\Project\Application\Query\ProjectListQuery;
use Sentinel\Project\Application\ReadModel\ProjectListResult;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ListProjectsHandler
{
    public function __construct(
        private ProjectListQuery $projects,
        private ProjectListingPolicy $projectListingPolicy,
        private TenantContext $tenantContext,
    ) {}

    public function handle(ListProjectsQuery $query): ProjectListResult
    {
        if (! $this->projectListingPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to list projects.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        return $this->projects->list($this->tenantContext->organizationId(), $query);
    }
}
