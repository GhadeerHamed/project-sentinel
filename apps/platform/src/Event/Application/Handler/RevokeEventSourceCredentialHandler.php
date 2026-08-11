<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Handler;

use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Event\Application\Command\RevokeEventSourceCredentialCommand;
use Sentinel\Event\Application\Policy\EventSourceCredentialRevocationPolicy;
use Sentinel\Event\Domain\Repository\EventSourceCredentialRepository;
use Sentinel\Event\Domain\Repository\EventSourceRepository;
use Sentinel\Event\Domain\ValueObject\EventSourceCredentialId;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class RevokeEventSourceCredentialHandler
{
    public function __construct(
        private ProjectLookupQuery $projects,
        private ApplicationRepository $applications,
        private EnvironmentRepository $environments,
        private EventSourceRepository $eventSources,
        private EventSourceCredentialRepository $credentials,
        private EventSourceCredentialRevocationPolicy $policy,
        private TenantContext $tenantContext,
        private Clock $clock,
        private TransactionManager $transactions,
        private UuidV7Generator $uuidGenerator,
        private AuditRecorder $audit,
    ) {}

    public function handle(RevokeEventSourceCredentialCommand $command): void
    {
        if (! $this->policy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to revoke Event Source credentials.');
        }

        $organizationId = $this->tenantContext->organizationId();
        $projectId = ProjectId::fromString($command->projectId());
        $this->projects->get($organizationId, $projectId);
        $applicationId = ApplicationId::fromString($command->applicationId());
        $this->applications->getById($applicationId, $organizationId, $projectId);
        $environmentId = EnvironmentId::fromString($command->environmentId());
        $this->environments->getById($environmentId, $organizationId, $applicationId);
        $eventSourceId = EventSourceId::fromString($command->eventSourceId());
        $eventSource = $this->eventSources->getById($eventSourceId, $organizationId);

        if (! $eventSource->projectId()->equals($projectId) || ! $eventSource->applicationId()->equals($applicationId) || ! $eventSource->environmentId()->equals($environmentId)) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::NOT_FOUND, 'The Event Source is not available in this hierarchy.');
        }

        $credentialId = EventSourceCredentialId::fromString($command->credentialId());
        $credential = $this->credentials->findById($credentialId, $organizationId);

        if ($credential === null || ! $credential->eventSourceId()->equals($eventSourceId)) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::NOT_FOUND, 'The Event Source credential is not available.');
        }

        $at = $this->clock->now();

        if (! $credential->isActiveAt($at)) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::INVALID_STATE, 'The Event Source credential is not active.');
        }

        $credential->revoke($at);

        $this->transactions->transactional(function () use ($credentialId, $eventSourceId, $organizationId, $at): void {
            if (! $this->credentials->revoke($credentialId, $eventSourceId, $organizationId, $at)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::INVALID_STATE, 'The Event Source credential is not active.');
            }

            $this->audit->record(AuditRecord::fromValues(
                id: $this->uuidGenerator->generate(), organizationId: $organizationId->value(),
                actorType: $this->tenantContext->actingIdentity()->identityType(), actorId: $this->tenantContext->actingIdentity()->identityId(),
                action: 'event_source.credential.revoke', resourceType: 'event_source_credential', resourceId: $credentialId->value(),
                occurredAt: $at, recordedAt: $at, correlationId: $this->tenantContext->correlationId()->value(), causationId: $this->tenantContext->causationId()?->value(),
            ));
        });
    }
}
