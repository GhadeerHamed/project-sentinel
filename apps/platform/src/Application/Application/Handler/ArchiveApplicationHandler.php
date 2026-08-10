<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Handler;

use Sentinel\Application\Application\Command\ArchiveApplicationCommand;
use Sentinel\Application\Application\Policy\ApplicationArchivalPolicy;
use Sentinel\Application\Domain\Exception\InvalidApplicationTransition;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class ArchiveApplicationHandler
{
    public function __construct(private ApplicationRepository $applications, private ProjectLookupQuery $projects, private ApplicationArchivalPolicy $policy, private TenantContext $tenantContext, private Clock $clock, private TransactionManager $transactions, private UuidV7Generator $uuidGenerator, private TenantScopedOperationRecorder $recorder) {}

    public function handle(ArchiveApplicationCommand $command): ResourceVersion
    {
        return $this->transactions->transactional(function () use ($command): ResourceVersion {
            if (! $this->policy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to archive applications.');
            }
            $organizationId = $this->tenantContext->organizationId();
            $this->projects->get($organizationId, $command->projectId());
            $application = $this->applications->getById($command->applicationId(), $organizationId, $command->projectId());
            try {
                $now = $this->clock->now();
                $archived = $application->archive($now, $this->uuidGenerator->generate(), $this->tenantContext->correlationId()->value(), $this->tenantContext->causationId()?->value());
            } catch (InvalidApplicationTransition) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::CONFLICT, 'The application is already archived.');
            }
            $this->applications->save($archived, $command->expectedVersion());
            /** @var list<AggregateDomainEvent> $events */
            $events = $archived->pullDomainEvents();
            $this->recorder->record($events, 'application.archive', 'application', $archived->id()->value(), $now);

            return $archived->version();
        });
    }
}
