<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Handler;

use Sentinel\Project\Application\Command\ArchiveProjectCommand;
use Sentinel\Project\Application\Policy\ProjectArchivalPolicy;
use Sentinel\Project\Domain\Exception\InvalidProjectTransition;
use Sentinel\Project\Domain\Repository\ProjectRepository;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class ArchiveProjectHandler
{
    public function __construct(
        private ProjectRepository $projects,
        private ProjectArchivalPolicy $projectArchivalPolicy,
        private TenantContext $tenantContext,
        private UuidV7Generator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
        private TenantScopedOperationRecorder $operationRecorder,
    ) {}

    public function handle(ArchiveProjectCommand $command): ResourceVersion
    {
        return $this->transactions->transactional(function () use ($command): ResourceVersion {
            $project = $this->projects->getById(
                $command->projectId(),
                $this->tenantContext->organizationId(),
            );

            if (! $this->projectArchivalPolicy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(
                    ErrorCategory::FORBIDDEN,
                    'The acting identity is not authorized to archive projects.',
                    ['organization_id' => $this->tenantContext->organizationId()->value()],
                );
            }

            $now = $this->clock->now();

            try {
                $archived = $project->archive(
                    at: $now,
                    actorIdentityId: $this->tenantContext->actingIdentity()->identityId(),
                    eventId: $this->uuidGenerator->generate(),
                    correlationId: $this->tenantContext->correlationId()->value(),
                    causationId: $this->tenantContext->causationId()?->value(),
                );
            } catch (InvalidProjectTransition) {
                throw ApplicationOperationFailure::forCategory(
                    ErrorCategory::CONFLICT,
                    'The project is already archived.',
                );
            }

            $this->projects->save($archived, $command->expectedVersion());

            /** @var list<AggregateDomainEvent> $events */
            $events = $archived->pullDomainEvents();
            $this->operationRecorder->record($events, 'project.archive', 'project', $archived->id()->value(), $now);

            return $archived->version();
        });
    }
}
