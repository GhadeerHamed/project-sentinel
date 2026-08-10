<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Handler;

use DateTimeImmutable;
use Sentinel\Project\Application\Command\CreateProjectCommand;
use Sentinel\Project\Application\Policy\ProjectCreationPolicy;
use Sentinel\Project\Application\Result\CreateProjectResult;
use Sentinel\Project\Domain\Entity\Project;
use Sentinel\Project\Domain\Repository\ProjectRepository;
use Sentinel\Project\Domain\ValueObject\ProjectId;
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

final readonly class CreateProjectHandler
{
    public function __construct(
        private ProjectRepository $projects,
        private ProjectCreationPolicy $projectCreationPolicy,
        private TenantContext $tenantContext,
        private IdempotentOperation $idempotentOperation,
        private UuidV7Generator $uuidGenerator,
        private TenantScopedOperationRecorder $operationRecorder,
    ) {}

    public function handle(CreateProjectCommand $command): CreateProjectResult
    {
        if (! $this->projectCreationPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to create projects.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        $request = IdempotencyRequest::fromNormalizedPayload(
            organizationId: $this->tenantContext->organizationId(),
            identityId: IdentityId::fromString($this->tenantContext->actingIdentity()->identityId()),
            operation: 'project.create',
            key: $command->idempotencyKey(),
            normalizedPayload: $command->normalizedPayload(),
        );
        $execution = $this->idempotentOperation->execute(
            $request,
            function (DateTimeImmutable $at) use ($command): IdempotencyResponse {
                $project = Project::create(
                    id: $this->uuidGenerator->generate(),
                    organizationId: $this->tenantContext->organizationId()->value(),
                    name: $command->name(),
                    slug: $command->slug(),
                    description: $command->description(),
                    createdByIdentityId: $this->tenantContext->actingIdentity()->identityId(),
                    at: $at,
                    eventId: $this->uuidGenerator->generate(),
                    correlationId: $this->tenantContext->correlationId()->value(),
                    causationId: $this->tenantContext->causationId()?->value(),
                );

                $this->projects->save($project, ExpectedVersion::none());

                /** @var list<AggregateDomainEvent> $events */
                $events = $project->pullDomainEvents();
                $this->operationRecorder->record($events, 'project.create', 'project', $project->id()->value(), $at);

                return IdempotencyResponse::fromValues(201, $project->id()->value());
            },
        );

        $project = $this->projects->getById(
            ProjectId::fromString($execution->response()->resourceReference()->value()),
            $this->tenantContext->organizationId(),
        );

        return CreateProjectResult::fromProject($project);
    }
}
