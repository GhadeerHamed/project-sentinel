<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Handler;

use DateTimeImmutable;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Application\Domain\ValueObject\ApplicationStatus;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Environment\Domain\ValueObject\EnvironmentStatus;
use Sentinel\Event\Application\Authentication\EventSourceIdentity;
use Sentinel\Event\Application\Command\IngestEventCommand;
use Sentinel\Event\Application\Result\IngestEventResult;
use Sentinel\Event\Domain\Entity\Event;
use Sentinel\Event\Domain\Event\EventAccepted;
use Sentinel\Event\Domain\Exception\EventExternalIdConflict;
use Sentinel\Event\Domain\Repository\EventRepository;
use Sentinel\Event\Domain\ValueObject\EventEnvelope;
use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Organization\Domain\ValueObject\OrganizationStatus;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\ValueObject\ProjectStatus;
use Sentinel\Shared\Application\Idempotency\IdempotentOperation;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRequest;
use Sentinel\Shared\Domain\Idempotency\IdempotencyResponse;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;

final readonly class IngestEventHandler
{
    public function __construct(
        private EventRepository $events,
        private OrganizationRepository $organizations,
        private ProjectLookupQuery $projects,
        private ApplicationRepository $applications,
        private EnvironmentRepository $environments,
        private IdempotentOperation $idempotentOperation,
        private UuidV7Generator $uuidGenerator,
        private OutboxMessageStore $outbox,
    ) {}

    public function handle(IngestEventCommand $command, EventSourceIdentity $sourceIdentity): IngestEventResult
    {
        $envelope = EventEnvelope::fromValues(
            externalEventId: $command->externalEventId(),
            eventType: $command->eventType(),
            schemaVersion: $command->schemaVersion(),
            severity: $command->severity(),
            occurredAt: $command->occurredAt(),
            payload: $command->payload(),
            attributes: $command->attributes(),
            correlationId: $command->correlationId(),
            causationId: $command->causationId(),
            traceId: $command->traceId(),
        );
        $this->assertActiveHierarchy($sourceIdentity);

        $request = IdempotencyRequest::fromValues(
            organizationId: $sourceIdentity->organizationId(),
            identityId: IdentityId::fromString($sourceIdentity->eventSourceId()->value()),
            operation: 'event.ingest',
            key: $command->idempotencyKey(),
            requestHash: $envelope->normalizedHash(),
        );
        $correlationId = $envelope->correlationId()?->value() ?? $this->uuidGenerator->generate();
        $execution = $this->idempotentOperation->execute(
            $request,
            function (DateTimeImmutable $acceptedAt) use ($sourceIdentity, $envelope, $correlationId): IdempotencyResponse {
                $event = Event::accept(
                    id: $this->uuidGenerator->generate(),
                    organizationId: $sourceIdentity->organizationId()->value(),
                    projectId: $sourceIdentity->projectId()->value(),
                    applicationId: $sourceIdentity->applicationId()->value(),
                    environmentId: $sourceIdentity->environmentId()->value(),
                    eventSourceId: $sourceIdentity->eventSourceId()->value(),
                    envelope: $envelope,
                    acceptedAt: $acceptedAt,
                    correlationId: $correlationId,
                );

                if (! $this->persistUnlessMatchingSourceDuplicate($event, $envelope)) {
                    $existing = $this->events->findByExternalEventId(
                        $sourceIdentity->organizationId(),
                        $sourceIdentity->eventSourceId(),
                        $envelope->externalEventId() ?? '',
                    );

                    if ($existing === null) {
                        throw new \LogicException('The source duplicate Event could not be loaded.');
                    }

                    return IdempotencyResponse::fromValues(202, $existing->id()->value());
                }

                $this->outbox->append(OutboxMessage::fromDomainEvent(
                    id: $this->uuidGenerator->generate(),
                    event: EventAccepted::fromEvent($event),
                    createdAt: $acceptedAt,
                ));

                return IdempotencyResponse::fromValues(202, $event->id()->value());
            },
        );

        return new IngestEventResult($this->events->getById(
            EventId::fromString($execution->response()->resourceReference()->value()),
            $sourceIdentity->organizationId(),
        ));
    }

    public function assertActiveHierarchy(EventSourceIdentity $sourceIdentity): void
    {
        $organization = $this->organizations->getById($sourceIdentity->organizationId());

        if ($organization->status() !== OrganizationStatus::ACTIVE) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'Events can only be ingested for active organizations.',
                ['organization_id' => $sourceIdentity->organizationId()->value()],
            );
        }

        $project = $this->projects->get($sourceIdentity->organizationId(), $sourceIdentity->projectId());

        if ($project->status() !== ProjectStatus::ACTIVE->value) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'Events can only be ingested for active projects.',
                ['project_id' => $sourceIdentity->projectId()->value()],
            );
        }

        $application = $this->applications->getById(
            $sourceIdentity->applicationId(),
            $sourceIdentity->organizationId(),
            $sourceIdentity->projectId(),
        );

        if ($application->status() !== ApplicationStatus::ACTIVE) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'Events can only be ingested for active applications.',
                ['application_id' => $sourceIdentity->applicationId()->value()],
            );
        }

        $environment = $this->environments->getById(
            $sourceIdentity->environmentId(),
            $sourceIdentity->organizationId(),
            $sourceIdentity->applicationId(),
        );

        if ($environment->status() !== EnvironmentStatus::ACTIVE) {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::INVALID_STATE,
                'Events can only be ingested for active environments.',
                ['environment_id' => $sourceIdentity->environmentId()->value()],
            );
        }
    }

    private function persistUnlessMatchingSourceDuplicate(Event $event, EventEnvelope $envelope): bool
    {
        $externalEventId = $envelope->externalEventId();

        if ($externalEventId === null) {
            $this->events->save($event);

            return true;
        }

        $existing = $this->events->findByExternalEventId(
            $event->organizationId(),
            $event->eventSourceId(),
            $externalEventId,
        );

        if ($existing !== null) {
            $this->assertMatchingSourceDuplicate($existing, $event);

            return false;
        }

        if ($this->events->saveIfExternalEventIdAbsent($event)) {
            return true;
        }

        $existing = $this->events->findByExternalEventId(
            $event->organizationId(),
            $event->eventSourceId(),
            $externalEventId,
        );

        if ($existing === null) {
            throw new \LogicException('The source duplicate Event could not be loaded.');
        }

        $this->assertMatchingSourceDuplicate($existing, $event);

        return false;
    }

    private function assertMatchingSourceDuplicate(Event $existing, Event $candidate): void
    {
        if (hash_equals($existing->normalizedEventHash(), $candidate->normalizedEventHash())) {
            return;
        }

        throw EventExternalIdConflict::forExternalEventId(
            $candidate->organizationId(),
            $candidate->eventSourceId(),
            $candidate->externalEventId() ?? '',
        );
    }
}
