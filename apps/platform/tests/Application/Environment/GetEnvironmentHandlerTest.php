<?php

declare(strict_types=1);

namespace Tests\Application\Environment;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Application\Application\Query\ApplicationLookupQuery;
use Sentinel\Application\Application\ReadModel\ApplicationListItem;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\Handler\GetEnvironmentHandler;
use Sentinel\Environment\Application\Policy\EnvironmentRetrievalPolicy;
use Sentinel\Environment\Application\Query\EnvironmentLookupQuery;
use Sentinel\Environment\Application\ReadModel\EnvironmentListItem;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class GetEnvironmentHandlerTest extends TestCase
{
    #[Test]
    public function it_reads_an_environment_using_tenant_and_application_scoped_projections_without_hydrating_an_aggregate(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f70');
        $projectId = ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f71');
        $applicationId = ApplicationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f72');
        $environmentId = EnvironmentId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f73');
        $projects = new class implements ProjectLookupQuery
        {
            public ?OrganizationId $organizationId = null;

            public ?ProjectId $projectId = null;

            public function get(OrganizationId $organizationId, ProjectId $projectId): ProjectListItem
            {
                $this->organizationId = $organizationId;
                $this->projectId = $projectId;

                return new ProjectListItem($projectId->value(), 'Commerce', 'commerce', null, 'active', 1, new DateTimeImmutable, new DateTimeImmutable);
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

                return new ApplicationListItem($applicationId->value(), $projectId->value(), 'Checkout', 'checkout', null, 'high', 'active', 1, new DateTimeImmutable, new DateTimeImmutable);
            }
        };
        $environments = new class implements EnvironmentLookupQuery
        {
            public ?OrganizationId $organizationId = null;

            public ?ApplicationId $applicationId = null;

            public ?EnvironmentId $environmentId = null;

            public function get(OrganizationId $organizationId, ApplicationId $applicationId, EnvironmentId $environmentId): EnvironmentListItem
            {
                $this->organizationId = $organizationId;
                $this->applicationId = $applicationId;
                $this->environmentId = $environmentId;

                return new EnvironmentListItem($environmentId->value(), $applicationId->value(), 'Production', 'production', 'production', 'active', 1, new DateTimeImmutable, new DateTimeImmutable);
            }
        };
        $handler = new GetEnvironmentHandler($environments, $projects, $applications, $this->policy(true), $this->tenantContext($organizationId));

        $environment = $handler->handle($projectId, $applicationId, $environmentId);

        self::assertInstanceOf(EnvironmentListItem::class, $environment);
        self::assertSame($organizationId->value(), $projects->organizationId?->value());
        self::assertSame($projectId->value(), $projects->projectId?->value());
        self::assertSame($organizationId->value(), $applications->organizationId?->value());
        self::assertSame($projectId->value(), $applications->projectId?->value());
        self::assertSame($applicationId->value(), $applications->applicationId?->value());
        self::assertSame($organizationId->value(), $environments->organizationId?->value());
        self::assertSame($applicationId->value(), $environments->applicationId?->value());
        self::assertSame($environmentId->value(), $environments->environmentId?->value());
    }

    #[Test]
    public function it_does_not_execute_parent_or_environment_queries_when_retrieval_is_not_authorized(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f74');
        $handler = new GetEnvironmentHandler(
            new class implements EnvironmentLookupQuery
            {
                public function get(OrganizationId $organizationId, ApplicationId $applicationId, EnvironmentId $environmentId): EnvironmentListItem
                {
                    throw new LogicException('The unauthorized handler must not look up an environment.');
                }
            },
            new class implements ProjectLookupQuery
            {
                public function get(OrganizationId $organizationId, ProjectId $projectId): ProjectListItem
                {
                    throw new LogicException('The unauthorized handler must not look up a project.');
                }
            },
            new class implements ApplicationLookupQuery
            {
                public function get(OrganizationId $organizationId, ProjectId $projectId, ApplicationId $applicationId): ApplicationListItem
                {
                    throw new LogicException('The unauthorized handler must not look up an application.');
                }
            },
            $this->policy(false),
            $this->tenantContext($organizationId),
        );

        try {
            $handler->handle(
                ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f75'),
                ApplicationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f76'),
                EnvironmentId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f77'),
            );
            self::fail('Expected environment retrieval authorization to be denied.');
        } catch (ApplicationOperationFailure $error) {
            self::assertSame(ErrorCategory::FORBIDDEN, $error->category());
        }
    }

    private function policy(bool $allows): EnvironmentRetrievalPolicy
    {
        return new class($allows) implements EnvironmentRetrievalPolicy
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
        return TenantContext::fromValues($organizationId->value(), '0198f3b6-48ae-7a44-9192-6f8029df5f78', 'user', '0198f3b6-48ae-7a44-9192-6f8029df5f79');
    }
}
