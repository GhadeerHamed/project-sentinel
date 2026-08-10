<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Handler;

use Sentinel\Application\Application\Query\ApplicationLookupQuery;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\Policy\EnvironmentListingPolicy;
use Sentinel\Environment\Application\Query\EnvironmentListQuery;
use Sentinel\Environment\Application\Query\ListEnvironmentsQuery;
use Sentinel\Environment\Application\ReadModel\EnvironmentListResult;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class ListEnvironmentsHandler
{
    public function __construct(
        private EnvironmentListQuery $environments,
        private ProjectLookupQuery $projects,
        private ApplicationLookupQuery $applications,
        private EnvironmentListingPolicy $environmentListingPolicy,
        private TenantContext $tenantContext,
    ) {}

    public function handle(ProjectId $projectId, ApplicationId $applicationId, ListEnvironmentsQuery $query): EnvironmentListResult
    {
        if (! $this->environmentListingPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to list environments.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        $organizationId = $this->tenantContext->organizationId();
        $this->projects->get($organizationId, $projectId);
        $this->applications->get($organizationId, $projectId, $applicationId);

        return $this->environments->list($organizationId, $applicationId, $query);
    }
}
