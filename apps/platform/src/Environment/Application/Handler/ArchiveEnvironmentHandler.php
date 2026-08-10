<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Handler;

use Sentinel\Application\Application\Query\ApplicationLookupQuery;
use Sentinel\Environment\Application\Command\ArchiveEnvironmentCommand;
use Sentinel\Environment\Application\Policy\EnvironmentArchivalPolicy;
use Sentinel\Environment\Application\Result\ArchiveEnvironmentResult;
use Sentinel\Environment\Domain\Exception\InvalidEnvironmentTransition;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class ArchiveEnvironmentHandler
{
    public function __construct(
        private EnvironmentRepository $environments,
        private ProjectLookupQuery $projects,
        private ApplicationLookupQuery $applications,
        private EnvironmentArchivalPolicy $environmentArchivalPolicy,
        private TenantContext $tenantContext,
        private Clock $clock,
        private TransactionManager $transactions,
        private UuidV7Generator $uuidGenerator,
        private TenantScopedOperationRecorder $operationRecorder,
    ) {}

    public function handle(ArchiveEnvironmentCommand $command): ArchiveEnvironmentResult
    {
        return $this->transactions->transactional(function () use ($command): ArchiveEnvironmentResult {
            if (! $this->environmentArchivalPolicy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(
                    ErrorCategory::FORBIDDEN,
                    'The acting identity is not authorized to archive environments.',
                    ['organization_id' => $this->tenantContext->organizationId()->value()],
                );
            }

            $organizationId = $this->tenantContext->organizationId();
            $this->projects->get($organizationId, $command->projectId());
            $this->applications->get($organizationId, $command->projectId(), $command->applicationId());
            $environment = $this->environments->getById($command->environmentId(), $organizationId, $command->applicationId());
            $now = $this->clock->now();

            try {
                $archived = $environment->archive(
                    at: $now,
                    actorIdentityId: $this->tenantContext->actingIdentity()->identityId(),
                    eventId: $this->uuidGenerator->generate(),
                    correlationId: $this->tenantContext->correlationId()->value(),
                    causationId: $this->tenantContext->causationId()?->value(),
                );
            } catch (InvalidEnvironmentTransition) {
                throw ApplicationOperationFailure::forCategory(
                    ErrorCategory::CONFLICT,
                    'The environment is already archived.',
                );
            }

            $this->environments->save($archived, $command->expectedVersion());

            /** @var list<AggregateDomainEvent> $events */
            $events = $archived->pullDomainEvents();
            $this->operationRecorder->record($events, 'environment.archive', 'environment', $archived->id()->value(), $now);

            return new ArchiveEnvironmentResult($archived->version());
        });
    }
}
