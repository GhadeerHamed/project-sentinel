<?php

declare(strict_types=1);

namespace Tests\Application\Project;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Project\Application\Handler\GetProjectHandler;
use Sentinel\Project\Application\Policy\ProjectRetrievalPolicy;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class GetProjectHandlerTest extends TestCase
{
    #[Test]
    public function it_uses_a_tenant_scoped_read_projection_without_hydrating_a_project_aggregate(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f40');
        $projectId = ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f41');
        $projects = new class implements ProjectLookupQuery
        {
            public ?OrganizationId $organizationId = null;

            public ?ProjectId $projectId = null;

            public function get(OrganizationId $organizationId, ProjectId $projectId): ProjectListItem
            {
                $this->organizationId = $organizationId;
                $this->projectId = $projectId;

                return new ProjectListItem(
                    id: $projectId->value(),
                    name: 'Commerce Platform',
                    slug: 'commerce-platform',
                    description: 'Customer-facing commerce services.',
                    status: 'active',
                    version: 3,
                    createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
                    updatedAt: new DateTimeImmutable('2026-08-10T13:00:00+00:00'),
                );
            }
        };
        $handler = new GetProjectHandler(
            projects: $projects,
            projectRetrievalPolicy: $this->policy(true),
            tenantContext: $this->tenantContext($organizationId),
        );

        $project = $handler->handle($projectId);

        self::assertInstanceOf(ProjectListItem::class, $project);
        self::assertSame($organizationId->value(), $projects->organizationId?->value());
        self::assertSame($projectId->value(), $projects->projectId?->value());
        self::assertSame('Commerce Platform', $project->name());
    }

    #[Test]
    public function it_does_not_lookup_a_project_when_retrieval_is_not_authorized(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f42');
        $projects = new class implements ProjectLookupQuery
        {
            public int $calls = 0;

            public function get(OrganizationId $organizationId, ProjectId $projectId): ProjectListItem
            {
                $this->calls++;

                throw new LogicException('The unauthorized handler must not execute the read projection.');
            }
        };
        $handler = new GetProjectHandler(
            projects: $projects,
            projectRetrievalPolicy: $this->policy(false),
            tenantContext: $this->tenantContext($organizationId),
        );

        try {
            $handler->handle(ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f43'));
            self::fail('Expected project retrieval authorization to be denied.');
        } catch (ApplicationOperationFailure $error) {
            self::assertSame(ErrorCategory::FORBIDDEN, $error->category());
            self::assertSame(0, $projects->calls);
        }
    }

    private function policy(bool $allows): ProjectRetrievalPolicy
    {
        return new class($allows) implements ProjectRetrievalPolicy
        {
            public function __construct(private bool $allows) {}

            public function allows(TenantContext $tenantContext): bool
            {
                return $this->allows;
            }
        };
    }

    private function tenantContext(OrganizationId $organizationId): TenantContext
    {
        return TenantContext::fromValues(
            organizationId: $organizationId->value(),
            identityId: '0198f3b6-48ae-7a44-9192-6f8029df5f44',
            identityType: 'user',
            correlationId: '0198f3b6-48ae-7a44-9192-6f8029df5f45',
        );
    }
}
