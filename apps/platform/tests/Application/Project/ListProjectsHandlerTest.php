<?php

declare(strict_types=1);

namespace Tests\Application\Project;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Project\Application\Handler\ListProjectsHandler;
use Sentinel\Project\Application\Policy\ProjectListingPolicy;
use Sentinel\Project\Application\Query\ListProjectsQuery;
use Sentinel\Project\Application\Query\ProjectListQuery;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Application\ReadModel\ProjectListResult;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class ListProjectsHandlerTest extends TestCase
{
    #[Test]
    public function it_uses_a_tenant_scoped_read_model_query_without_hydrating_project_aggregates(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f10');
        $query = new class implements ProjectListQuery
        {
            public ?OrganizationId $organizationId = null;

            public ?ListProjectsQuery $query = null;

            public function list(OrganizationId $organizationId, ListProjectsQuery $query): ProjectListResult
            {
                $this->organizationId = $organizationId;
                $this->query = $query;

                return new ProjectListResult([new ProjectListItem(
                    id: '0198f3b6-48ae-7a44-9192-6f8029df5f11',
                    name: 'Commerce Platform',
                    slug: 'commerce-platform',
                    description: null,
                    status: 'active',
                    version: 1,
                    createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
                    updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
                )], null);
            }
        };
        $handler = new ListProjectsHandler(
            projects: $query,
            projectListingPolicy: new class implements ProjectListingPolicy
            {
                public function allows(TenantContext $tenantContext): bool
                {
                    return true;
                }
            },
            tenantContext: $this->tenantContext($organizationId),
        );

        $projects = $handler->handle(ListProjectsQuery::fromValues(null, 'Commerce Platform', null));

        self::assertCount(1, $projects->projects());
        self::assertInstanceOf(ProjectListItem::class, $projects->projects()[0]);
        self::assertSame($organizationId->value(), $query->organizationId?->value());
        self::assertSame('active', $query->query?->status()->value);
        self::assertSame('Commerce Platform', $query->query?->name());
    }

    #[Test]
    public function it_does_not_execute_the_read_query_when_listing_is_not_authorized(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f12');
        $query = new class implements ProjectListQuery
        {
            public int $calls = 0;

            public function list(OrganizationId $organizationId, ListProjectsQuery $query): ProjectListResult
            {
                $this->calls++;

                return new ProjectListResult([], null);
            }
        };
        $handler = new ListProjectsHandler(
            projects: $query,
            projectListingPolicy: new class implements ProjectListingPolicy
            {
                public function allows(TenantContext $tenantContext): bool
                {
                    return false;
                }
            },
            tenantContext: $this->tenantContext($organizationId),
        );

        try {
            $handler->handle(ListProjectsQuery::fromValues(null, null, null));
            self::fail('Expected listing authorization to be denied.');
        } catch (ApplicationOperationFailure $error) {
            self::assertSame(ErrorCategory::FORBIDDEN, $error->category());
            self::assertSame(0, $query->calls);
        }
    }

    private function tenantContext(OrganizationId $organizationId): TenantContext
    {
        return TenantContext::fromValues(
            organizationId: $organizationId->value(),
            identityId: '0198f3b6-48ae-7a44-9192-6f8029df5f13',
            identityType: 'user',
            correlationId: '0198f3b6-48ae-7a44-9192-6f8029df5f14',
        );
    }
}
