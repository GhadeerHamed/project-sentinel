<?php

declare(strict_types=1);

namespace Tests\Application\Environment;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Application\Application\Query\ApplicationLookupQuery;
use Sentinel\Application\Application\ReadModel\ApplicationListItem;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\Handler\ListEnvironmentsHandler;
use Sentinel\Environment\Application\Policy\EnvironmentListingPolicy;
use Sentinel\Environment\Application\Query\EnvironmentListQuery;
use Sentinel\Environment\Application\Query\ListEnvironmentsQuery;
use Sentinel\Environment\Application\ReadModel\EnvironmentListItem;
use Sentinel\Environment\Application\ReadModel\EnvironmentListResult;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class ListEnvironmentsHandlerTest extends TestCase
{
    #[Test]
    public function it_uses_application_scoped_read_model_queries_without_hydrating_environment_aggregates(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f10');
        $projectId = ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f11');
        $applicationId = ApplicationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f12');
        $environments = new class implements EnvironmentListQuery
        {
            public ?OrganizationId $organizationId = null;

            public ?ApplicationId $applicationId = null;

            public ?ListEnvironmentsQuery $query = null;

            public function list(OrganizationId $organizationId, ApplicationId $applicationId, ListEnvironmentsQuery $query): EnvironmentListResult
            {
                $this->organizationId = $organizationId;
                $this->applicationId = $applicationId;
                $this->query = $query;

                return new EnvironmentListResult([new EnvironmentListItem(
                    id: '0198f3b6-48ae-7a44-9192-6f8029df5f13',
                    applicationId: $applicationId->value(),
                    name: 'Production',
                    slug: 'production',
                    environmentType: 'production',
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
        $applications = new class implements ApplicationLookupQuery
        {
            public ?OrganizationId $organizationId = null;

            public ?ProjectId $projectId = null;

            public ?ApplicationId $applicationId = null;

            public function get(OrganizationId $organizationId, ProjectId $projectId, ApplicationId $applicationId): ApplicationListItem
            {
                $this->organizationId = $organizationId;
                $this->projectId = $projectId;
                $this->applicationId = $applicationId;

                return new ApplicationListItem(
                    id: $applicationId->value(),
                    projectId: $projectId->value(),
                    name: 'Checkout API',
                    slug: 'checkout-api',
                    description: null,
                    criticality: 'high',
                    status: 'active',
                    version: 1,
                    createdAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
                    updatedAt: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
                );
            }
        };
        $handler = new ListEnvironmentsHandler(
            environments: $environments,
            projects: $projects,
            applications: $applications,
            environmentListingPolicy: new class implements EnvironmentListingPolicy
            {
                public function allows(TenantContext $tenantContext): bool
                {
                    return true;
                }
            },
            tenantContext: $this->tenantContext($organizationId),
        );

        $result = $handler->handle($projectId, $applicationId, ListEnvironmentsQuery::fromValues(null, 'production', null));

        self::assertCount(1, $result->environments());
        self::assertInstanceOf(EnvironmentListItem::class, $result->environments()[0]);
        self::assertSame($organizationId->value(), $projects->organizationId?->value());
        self::assertSame($projectId->value(), $projects->projectId?->value());
        self::assertSame($organizationId->value(), $applications->organizationId?->value());
        self::assertSame($projectId->value(), $applications->projectId?->value());
        self::assertSame($applicationId->value(), $applications->applicationId?->value());
        self::assertSame($organizationId->value(), $environments->organizationId?->value());
        self::assertSame($applicationId->value(), $environments->applicationId?->value());
        self::assertSame('active', $environments->query?->status()->value);
        self::assertSame('production', $environments->query?->environmentType()?->value);
    }

    #[Test]
    public function it_does_not_execute_parent_or_environment_queries_when_listing_is_not_authorized(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f20');
        $projectId = ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f21');
        $applicationId = ApplicationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f22');
        $environments = new class implements EnvironmentListQuery
        {
            public int $calls = 0;

            public function list(OrganizationId $organizationId, ApplicationId $applicationId, ListEnvironmentsQuery $query): EnvironmentListResult
            {
                $this->calls++;

                return new EnvironmentListResult([], null);
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
        $applications = new class implements ApplicationLookupQuery
        {
            public int $calls = 0;

            public function get(OrganizationId $organizationId, ProjectId $projectId, ApplicationId $applicationId): ApplicationListItem
            {
                $this->calls++;

                throw new \LogicException('The parent query must not execute.');
            }
        };
        $handler = new ListEnvironmentsHandler(
            environments: $environments,
            projects: $projects,
            applications: $applications,
            environmentListingPolicy: new class implements EnvironmentListingPolicy
            {
                public function allows(TenantContext $tenantContext): bool
                {
                    return false;
                }
            },
            tenantContext: $this->tenantContext($organizationId),
        );

        try {
            $handler->handle($projectId, $applicationId, ListEnvironmentsQuery::fromValues(null, null, null));
            self::fail('Expected listing authorization to be denied.');
        } catch (ApplicationOperationFailure $error) {
            self::assertSame(ErrorCategory::FORBIDDEN, $error->category());
            self::assertSame(0, $projects->calls);
            self::assertSame(0, $applications->calls);
            self::assertSame(0, $environments->calls);
        }
    }

    private function tenantContext(OrganizationId $organizationId): TenantContext
    {
        return TenantContext::fromValues(
            organizationId: $organizationId->value(),
            identityId: '0198f3b6-48ae-7a44-9192-6f8029df5f23',
            identityType: 'user',
            correlationId: '0198f3b6-48ae-7a44-9192-6f8029df5f24',
        );
    }
}
