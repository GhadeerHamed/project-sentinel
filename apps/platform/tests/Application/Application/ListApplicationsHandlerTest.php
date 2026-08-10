<?php

declare(strict_types=1);

namespace Tests\Application\Application;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Application\Application\Handler\ListApplicationsHandler;
use Sentinel\Application\Application\Policy\ApplicationListingPolicy;
use Sentinel\Application\Application\Query\ApplicationListQuery;
use Sentinel\Application\Application\Query\ListApplicationsQuery;
use Sentinel\Application\Application\ReadModel\ApplicationListItem;
use Sentinel\Application\Application\ReadModel\ApplicationListResult;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class ListApplicationsHandlerTest extends TestCase
{
    #[Test]
    public function it_uses_a_project_scoped_read_model_query_without_hydrating_application_aggregates(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f10');
        $projectId = ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f11');
        $applications = new class implements ApplicationListQuery
        {
            public ?OrganizationId $organizationId = null;

            public ?ProjectId $projectId = null;

            public ?ListApplicationsQuery $query = null;

            public function list(OrganizationId $organizationId, ProjectId $projectId, ListApplicationsQuery $query): ApplicationListResult
            {
                $this->organizationId = $organizationId;
                $this->projectId = $projectId;
                $this->query = $query;

                return new ApplicationListResult([new ApplicationListItem(
                    id: '0198f3b6-48ae-7a44-9192-6f8029df5f12',
                    projectId: $projectId->value(),
                    name: 'Checkout API',
                    slug: 'checkout-api',
                    description: null,
                    criticality: 'high',
                    status: 'active',
                    version: 1,
                    createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
                    updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
                )], null);
            }
        };
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
                    description: null,
                    status: 'active',
                    version: 1,
                    createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
                    updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
                );
            }
        };
        $handler = new ListApplicationsHandler(
            applications: $applications,
            projects: $projects,
            applicationListingPolicy: new class implements ApplicationListingPolicy
            {
                public function allows(TenantContext $tenantContext): bool
                {
                    return true;
                }
            },
            tenantContext: $this->tenantContext($organizationId),
        );

        $result = $handler->handle($projectId, ListApplicationsQuery::fromValues(null, 'Checkout API', null));

        self::assertCount(1, $result->applications());
        self::assertInstanceOf(ApplicationListItem::class, $result->applications()[0]);
        self::assertSame($organizationId->value(), $projects->organizationId?->value());
        self::assertSame($projectId->value(), $projects->projectId?->value());
        self::assertSame($organizationId->value(), $applications->organizationId?->value());
        self::assertSame($projectId->value(), $applications->projectId?->value());
        self::assertSame('active', $applications->query?->status()->value);
        self::assertSame('Checkout API', $applications->query?->name());
    }

    #[Test]
    public function it_does_not_execute_parent_or_application_queries_when_listing_is_not_authorized(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f20');
        $projectId = ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f21');
        $applications = new class implements ApplicationListQuery
        {
            public int $calls = 0;

            public function list(OrganizationId $organizationId, ProjectId $projectId, ListApplicationsQuery $query): ApplicationListResult
            {
                $this->calls++;

                return new ApplicationListResult([], null);
            }
        };
        $projects = new class implements ProjectLookupQuery
        {
            public int $calls = 0;

            public function get(OrganizationId $organizationId, ProjectId $projectId): ProjectListItem
            {
                $this->calls++;

                throw new \LogicException('The parent query must not execute.');
            }
        };
        $handler = new ListApplicationsHandler(
            applications: $applications,
            projects: $projects,
            applicationListingPolicy: new class implements ApplicationListingPolicy
            {
                public function allows(TenantContext $tenantContext): bool
                {
                    return false;
                }
            },
            tenantContext: $this->tenantContext($organizationId),
        );

        try {
            $handler->handle($projectId, ListApplicationsQuery::fromValues(null, null, null));
            self::fail('Expected listing authorization to be denied.');
        } catch (ApplicationOperationFailure $error) {
            self::assertSame(ErrorCategory::FORBIDDEN, $error->category());
            self::assertSame(0, $projects->calls);
            self::assertSame(0, $applications->calls);
        }
    }

    private function tenantContext(OrganizationId $organizationId): TenantContext
    {
        return TenantContext::fromValues(
            organizationId: $organizationId->value(),
            identityId: '0198f3b6-48ae-7a44-9192-6f8029df5f22',
            identityType: 'user',
            correlationId: '0198f3b6-48ae-7a44-9192-6f8029df5f23',
        );
    }
}
