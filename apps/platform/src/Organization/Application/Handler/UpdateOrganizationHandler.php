<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Handler;

use Sentinel\Organization\Application\Command\UpdateOrganizationCommand;
use Sentinel\Organization\Application\Policy\OrganizationUpdatePolicy;
use Sentinel\Organization\Application\Result\UpdateOrganizationResult;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class UpdateOrganizationHandler
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private OrganizationUpdatePolicy $organizationUpdatePolicy,
        private TenantContext $tenantContext,
        private UuidV7Generator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactionManager,
        private OutboxMessageStore $outboxMessageStore,
        private AuditRecorder $auditRecorder,
    ) {}

    public function handle(UpdateOrganizationCommand $command): UpdateOrganizationResult
    {
        return $this->transactionManager->transactional(function () use ($command): UpdateOrganizationResult {
            if (! $this->organizationUpdatePolicy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(
                    ErrorCategory::FORBIDDEN,
                    'The acting identity is not authorized to update this organization.',
                    ['organization_id' => $this->tenantContext->organizationId()->value()],
                );
            }

            $organization = $this->organizationRepository->getById($this->tenantContext->organizationId());
            $now = $this->clock->now();
            $updated = $organization->updateProfile(
                name: $command->name() ?? $organization->name(),
                timezone: $command->timezone() ?? $organization->timezone(),
                at: $now,
                actorIdentityId: $this->tenantContext->actingIdentity()->identityId(),
                eventId: $this->uuidGenerator->generate(),
                correlationId: $this->tenantContext->correlationId()->value(),
                causationId: $this->tenantContext->causationId()?->value(),
            );

            $this->organizationRepository->save($updated, $command->expectedVersion());

            /** @var list<AggregateDomainEvent> $events */
            $events = $updated->pullDomainEvents();
            foreach ($events as $event) {
                $this->outboxMessageStore->append(OutboxMessage::fromDomainEvent($this->uuidGenerator->generate(), $event, $now));
            }

            $this->auditRecorder->record(AuditRecord::fromValues(
                id: $this->uuidGenerator->generate(),
                organizationId: $updated->id()->value(),
                actorType: $this->tenantContext->actingIdentity()->identityType(),
                actorId: $this->tenantContext->actingIdentity()->identityId(),
                action: 'organization.update',
                resourceType: 'organization',
                resourceId: $updated->id()->value(),
                occurredAt: $now,
                recordedAt: $now,
                correlationId: $this->tenantContext->correlationId()->value(),
                causationId: $this->tenantContext->causationId()?->value(),
            ));

            return new UpdateOrganizationResult($updated->id(), $updated->name(), $updated->timezone(), $updated->version());
        });
    }
}
