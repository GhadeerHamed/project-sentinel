<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Handler;

use DateTimeImmutable;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Application\Domain\ValueObject\ApplicationStatus;
use Sentinel\Environment\Application\Command\CreateEnvironmentCommand;
use Sentinel\Environment\Application\Policy\EnvironmentCreationPolicy;
use Sentinel\Environment\Application\Result\CreateEnvironmentResult;
use Sentinel\Environment\Domain\Entity\Environment;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
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

final readonly class CreateEnvironmentHandler
{
    public function __construct(
        private EnvironmentRepository $environments,
        private ProjectLookupQuery $projects,
        private ApplicationRepository $applications,
        private EnvironmentCreationPolicy $environmentCreationPolicy,
        private TenantContext $tenantContext,
        private IdempotentOperation $idempotentOperation,
        private UuidV7Generator $uuidGenerator,
        private TenantScopedOperationRecorder $operationRecorder,
    ) {}

    public function handle(CreateEnvironmentCommand $command): CreateEnvironmentResult
    {
        if (! $this->environmentCreationPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to create environments.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        $projectId = ProjectId::fromString($command->projectId());
        $project = $this->projects->get($this->tenantContext->organizationId(), $projectId);

        if ($project->status() !== ProjectStatus::ACTIVE->value) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'Environments can only be created in active projects.',
                [
                    'organization_id' => $this->tenantContext->organizationId()->value(),
                    'project_id' => $projectId->value(),
                ],
            );
        }

        $applicationId = ApplicationId::fromString($command->applicationId());
        $application = $this->applications->getById(
            $applicationId,
            $this->tenantContext->organizationId(),
            $projectId,
        );

        if ($application->status() !== ApplicationStatus::ACTIVE) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'Environments can only be created in active applications.',
                [
                    'organization_id' => $this->tenantContext->organizationId()->value(),
                    'project_id' => $projectId->value(),
                    'application_id' => $applicationId->value(),
                ],
            );
        }

        $request = IdempotencyRequest::fromNormalizedPayload(
            organizationId: $this->tenantContext->organizationId(),
            identityId: IdentityId::fromString($this->tenantContext->actingIdentity()->identityId()),
            operation: 'environment.create',
            key: $command->idempotencyKey(),
            normalizedPayload: $command->normalizedPayload(),
        );
        $execution = $this->idempotentOperation->execute(
            $request,
            function (DateTimeImmutable $at) use ($applicationId, $command): IdempotencyResponse {
                $environment = Environment::create(
                    id: $this->uuidGenerator->generate(),
                    organizationId: $this->tenantContext->organizationId()->value(),
                    applicationId: $applicationId->value(),
                    name: $command->name(),
                    slug: $command->slug(),
                    environmentType: $command->environmentType(),
                    createdByIdentityId: $this->tenantContext->actingIdentity()->identityId(),
                    at: $at,
                    eventId: $this->uuidGenerator->generate(),
                    correlationId: $this->tenantContext->correlationId()->value(),
                    causationId: $this->tenantContext->causationId()?->value(),
                );

                $this->environments->save($environment, ExpectedVersion::none());

                /** @var list<AggregateDomainEvent> $events */
                $events = $environment->pullDomainEvents();
                $this->operationRecorder->record($events, 'environment.create', 'environment', $environment->id()->value(), $at);

                return IdempotencyResponse::fromValues(201, $environment->id()->value());
            },
        );

        $environment = $this->environments->getById(
            EnvironmentId::fromString($execution->response()->resourceReference()->value()),
            $this->tenantContext->organizationId(),
            $applicationId,
        );

        return CreateEnvironmentResult::fromEnvironment($environment);
    }
}
