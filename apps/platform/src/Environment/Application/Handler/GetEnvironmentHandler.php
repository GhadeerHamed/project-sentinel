<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Handler;

use Sentinel\Application\Application\Query\ApplicationLookupQuery;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\Policy\EnvironmentRetrievalPolicy;
use Sentinel\Environment\Application\Query\EnvironmentLookupQuery;
use Sentinel\Environment\Application\ReadModel\EnvironmentListItem;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class GetEnvironmentHandler
{
    public function __construct(
        private EnvironmentLookupQuery $environments,
        private ProjectLookupQuery $projects,
        private ApplicationLookupQuery $applications,
        private EnvironmentRetrievalPolicy $environmentRetrievalPolicy,
        private TenantContext $tenantContext,
    ) {}

    public function handle(ProjectId $projectId, ApplicationId $applicationId, EnvironmentId $environmentId): EnvironmentListItem
    {
        if (! $this->environmentRetrievalPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to read environments.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        $organizationId = $this->tenantContext->organizationId();
        $this->projects->get($organizationId, $projectId);
        $this->applications->get($organizationId, $projectId, $applicationId);

        return $this->environments->get($organizationId, $applicationId, $environmentId);
    }
}
