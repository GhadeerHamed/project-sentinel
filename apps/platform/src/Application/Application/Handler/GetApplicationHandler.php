<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Handler;

use Sentinel\Application\Application\Policy\ApplicationRetrievalPolicy;
use Sentinel\Application\Application\Query\ApplicationLookupQuery;
use Sentinel\Application\Application\ReadModel\ApplicationListItem;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class GetApplicationHandler
{
    public function __construct(
        private ApplicationLookupQuery $applications,
        private ProjectLookupQuery $projects,
        private ApplicationRetrievalPolicy $applicationRetrievalPolicy,
        private TenantContext $tenantContext,
    ) {}

    public function handle(ProjectId $projectId, ApplicationId $applicationId): ApplicationListItem
    {
        if (! $this->applicationRetrievalPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to read applications.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        $organizationId = $this->tenantContext->organizationId();
        $this->projects->get($organizationId, $projectId);

        return $this->applications->get($organizationId, $projectId, $applicationId);
    }
}
