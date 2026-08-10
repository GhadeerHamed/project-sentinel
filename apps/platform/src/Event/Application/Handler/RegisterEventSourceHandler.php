<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Handler;

use DateTimeImmutable;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Application\Domain\ValueObject\ApplicationStatus;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentStatus;
use Sentinel\Event\Application\Authentication\EventSourceCredentialIssuer;
use Sentinel\Event\Application\Command\RegisterEventSourceCommand;
use Sentinel\Event\Application\Policy\EventSourceRegistrationPolicy;
use Sentinel\Event\Application\Result\IssuedEventSourceCredential;
use Sentinel\Event\Application\Result\RegisterEventSourceResult;
use Sentinel\Event\Domain\Entity\EventSource;
use Sentinel\Event\Domain\Repository\EventSourceCredentialRepository;
use Sentinel\Event\Domain\Repository\EventSourceRepository;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Project\Domain\ValueObject\ProjectStatus;
use Sentinel\Shared\Application\Idempotency\IdempotentOperation;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRequest;
use Sentinel\Shared\Domain\Idempotency\IdempotencyResponse;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class RegisterEventSourceHandler
{
    public function __construct(
        private EventSourceRepository $eventSources,
        private EventSourceCredentialRepository $credentials,
        private ProjectLookupQuery $projects,
        private ApplicationRepository $applications,
        private EnvironmentRepository $environments,
        private EventSourceRegistrationPolicy $registrationPolicy,
        private TenantContext $tenantContext,
        private IdempotentOperation $idempotentOperation,
        private UuidV7Generator $uuidGenerator,
        private EventSourceCredentialIssuer $credentialIssuer,
        private AuditRecorder $audit,
    ) {}

    public function handle(RegisterEventSourceCommand $command): RegisterEventSourceResult
    {
        if (! $this->registrationPolicy->allows($this->tenantContext)) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::FORBIDDEN,
                'The acting identity is not authorized to register Event Sources.',
                ['organization_id' => $this->tenantContext->organizationId()->value()],
            );
        }

        $projectId = ProjectId::fromString($command->projectId());
        $project = $this->projects->get($this->tenantContext->organizationId(), $projectId);

        if ($project->status() !== ProjectStatus::ACTIVE->value) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'Event Sources can only be registered in active projects.',
                ['project_id' => $projectId->value()],
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
                'Event Sources can only be registered in active applications.',
                ['application_id' => $applicationId->value()],
            );
        }

        $environmentId = EnvironmentId::fromString($command->environmentId());
        $environment = $this->environments->getById(
            $environmentId,
            $this->tenantContext->organizationId(),
            $applicationId,
        );

        if ($environment->status() !== EnvironmentStatus::ACTIVE) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'Event Sources can only be registered in active environments.',
                ['environment_id' => $environmentId->value()],
            );
        }

        $request = IdempotencyRequest::fromNormalizedPayload(
            organizationId: $this->tenantContext->organizationId(),
            identityId: IdentityId::fromString($this->tenantContext->actingIdentity()->identityId()),
            operation: 'event_source.create',
            key: $command->idempotencyKey(),
            normalizedPayload: $command->normalizedPayload(),
        );
        /** @var ?IssuedEventSourceCredential $issuedCredential */
        $issuedCredential = null;
        $execution = $this->idempotentOperation->execute(
            $request,
            function (DateTimeImmutable $at) use ($projectId, $applicationId, $environmentId, $command, &$issuedCredential): IdempotencyResponse {
                $eventSource = EventSource::create(
                    id: $this->uuidGenerator->generate(),
                    organizationId: $this->tenantContext->organizationId()->value(),
                    projectId: $projectId->value(),
                    applicationId: $applicationId->value(),
                    environmentId: $environmentId->value(),
                    name: $command->name(),
                    at: $at,
                );
                $issuedCredential = $this->credentialIssuer->issue(
                    credentialId: $this->uuidGenerator->generate(),
                    organizationId: $this->tenantContext->organizationId()->value(),
                    eventSourceId: $eventSource->id()->value(),
                    at: $at,
                );

                $this->eventSources->save($eventSource);
                $this->credentials->save($issuedCredential->credential());
                $this->recordAudit($eventSource->id()->value(), 'event_source.create', 'event_source', $at);
                $this->recordAudit($issuedCredential->credential()->id()->value(), 'event_source.credential.issue', 'event_source_credential', $at);

                return IdempotencyResponse::fromValues(201, $eventSource->id()->value());
            },
        );

        $eventSource = $this->eventSources->getById(
            EventSourceId::fromString($execution->response()->resourceReference()->value()),
            $this->tenantContext->organizationId(),
        );

        return new RegisterEventSourceResult($eventSource, $issuedCredential);
    }

    private function recordAudit(string $resourceId, string $action, string $resourceType, DateTimeImmutable $at): void
    {
        $this->audit->record(AuditRecord::fromValues(
            id: $this->uuidGenerator->generate(),
            organizationId: $this->tenantContext->organizationId()->value(),
            actorType: $this->tenantContext->actingIdentity()->identityType(),
            actorId: $this->tenantContext->actingIdentity()->identityId(),
            action: $action,
            resourceType: $resourceType,
            resourceId: $resourceId,
            occurredAt: $at,
            recordedAt: $at,
            correlationId: $this->tenantContext->correlationId()->value(),
            causationId: $this->tenantContext->causationId()?->value(),
        ));
    }
}
