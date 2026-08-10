<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Handler;

use Sentinel\Project\Application\Policy\ProjectRetrievalPolicy;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class GetProjectHandler
{
    public function __construct(
        private ProjectLookupQuery $projects,
        private ProjectRetrievalPolicy $projectRetrievalPolicy,
        private TenantContext $tenantContext,
    ) {}

    public function handle(ProjectId $projectId): ProjectListItem
    {
        if (! $this->projectRetrievalPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to read projects.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        return $this->projects->get($this->tenantContext->organizationId(), $projectId);
    }
}
