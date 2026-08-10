<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Handler;

use DateTimeImmutable;
use Sentinel\Application\Application\Command\CreateApplicationCommand;
use Sentinel\Application\Application\Policy\ApplicationCreationPolicy;
use Sentinel\Application\Application\Result\CreateApplicationResult;
use Sentinel\Application\Domain\Entity\Application;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Project\Domain\ValueObject\ProjectStatus;
use Sentinel\Shared\Application\Idempotency\IdempotentOperation;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRequest;
use Sentinel\Shared\Domain\Idempotency\IdempotencyResponse;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class CreateApplicationHandler
{
    public function __construct(
        private ApplicationRepository $applications,
        private ProjectLookupQuery $projects,
        private ApplicationCreationPolicy $applicationCreationPolicy,
        private TenantContext $tenantContext,
        private IdempotentOperation $idempotentOperation,
        private UuidV7Generator $uuidGenerator,
        private TenantScopedOperationRecorder $operationRecorder,
    ) {}

    public function handle(CreateApplicationCommand $command): CreateApplicationResult
    {
        if (! $this->applicationCreationPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to create applications.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        $projectId = ProjectId::fromString($command->projectId());
        $project = $this->projects->get($this->tenantContext->organizationId(), $projectId);

        if ($project->status() !== ProjectStatus::ACTIVE->value) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'Applications can only be created in active projects.',
                [
                    'organization_id' => $this->tenantContext->organizationId()->value(),
                    'project_id' => $projectId->value(),
                ],
            );
        }

        $request = IdempotencyRequest::fromNormalizedPayload(
            organizationId: $this->tenantContext->organizationId(),
            identityId: IdentityId::fromString($this->tenantContext->actingIdentity()->identityId()),
            operation: 'application.create',
            key: $command->idempotencyKey(),
            normalizedPayload: $command->normalizedPayload(),
        );
        $execution = $this->idempotentOperation->execute(
            $request,
            function (DateTimeImmutable $at) use ($command, $projectId): IdempotencyResponse {
                $application = Application::create(
                    id: $this->uuidGenerator->generate(),
                    organizationId: $this->tenantContext->organizationId()->value(),
                    projectId: $projectId->value(),
                    name: $command->name(),
                    slug: $command->slug(),
                    description: $command->description(),
                    criticality: $command->criticality(),
                    createdByIdentityId: $this->tenantContext->actingIdentity()->identityId(),
                    at: $at,
                    eventId: $this->uuidGenerator->generate(),
                    correlationId: $this->tenantContext->correlationId()->value(),
                    causationId: $this->tenantContext->causationId()?->value(),
                );

                $this->applications->save($application, ExpectedVersion::none());

                /** @var list<AggregateDomainEvent> $events */
                $events = $application->pullDomainEvents();
                $this->operationRecorder->record($events, 'application.create', 'application', $application->id()->value(), $at);

                return IdempotencyResponse::fromValues(201, $application->id()->value());
            },
        );

        $application = $this->applications->getById(
            ApplicationId::fromString($execution->response()->resourceReference()->value()),
            $this->tenantContext->organizationId(),
            $projectId,
        );

        return CreateApplicationResult::fromApplication($application);
    }
}
