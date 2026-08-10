<?php

declare(strict_types=1);

namespace Tests\Application\Application;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Application\Application\Handler\GetApplicationHandler;
use Sentinel\Application\Application\Policy\ApplicationRetrievalPolicy;
use Sentinel\Application\Application\Query\ApplicationLookupQuery;
use Sentinel\Application\Application\ReadModel\ApplicationListItem;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class GetApplicationHandlerTest extends TestCase
{
    #[Test]
    public function it_reads_an_application_with_a_tenant_and_project_scoped_projection_without_hydrating_an_aggregate(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f60');
        $projectId = ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f61');
        $applicationId = ApplicationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f62');
        $projects = new class implements ProjectLookupQuery
        {
            public int $calls = 0;

            public function get(OrganizationId $organizationId, ProjectId $projectId): ProjectListItem
            {
                $this->calls++;

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

                return new ApplicationListItem($applicationId->value(), $projectId->value(), 'Checkout API', 'checkout-api', null, 'high', 'active', 3, new DateTimeImmutable, new DateTimeImmutable);
            }
        };
        $handler = new GetApplicationHandler($applications, $projects, $this->policy(true), $this->tenantContext($organizationId));

        $application = $handler->handle($projectId, $applicationId);

        self::assertInstanceOf(ApplicationListItem::class, $application);
        self::assertSame(1, $projects->calls);
        self::assertSame($organizationId->value(), $applications->organizationId?->value());
        self::assertSame($projectId->value(), $applications->projectId?->value());
        self::assertSame($applicationId->value(), $applications->applicationId?->value());
    }

    #[Test]
    public function it_does_not_lookup_a_project_or_application_when_retrieval_is_not_authorized(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f63');
        $projects = new class implements ProjectLookupQuery
        {
            public function get(OrganizationId $organizationId, ProjectId $projectId): ProjectListItem
            {
                throw new LogicException('The unauthorized handler must not lookup a project.');
            }
        };
        $applications = new class implements ApplicationLookupQuery
        {
            public function get(OrganizationId $organizationId, ProjectId $projectId, ApplicationId $applicationId): ApplicationListItem
            {
                throw new LogicException('The unauthorized handler must not lookup an application.');
            }
        };
        $handler = new GetApplicationHandler($applications, $projects, $this->policy(false), $this->tenantContext($organizationId));

        try {
            $handler->handle(
                ProjectId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f64'),
                ApplicationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f65'),
            );
            self::fail('Expected application retrieval authorization to be denied.');
        } catch (ApplicationOperationFailure $error) {
            self::assertSame(ErrorCategory::FORBIDDEN, $error->category());
        }
    }

    private function policy(bool $allows): ApplicationRetrievalPolicy
    {
        return new class($allows) implements ApplicationRetrievalPolicy
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
        return TenantContext::fromValues($organizationId->value(), '0198f3b6-48ae-7a44-9192-6f8029df5f66', 'user', '0198f3b6-48ae-7a44-9192-6f8029df5f67');
    }
}
