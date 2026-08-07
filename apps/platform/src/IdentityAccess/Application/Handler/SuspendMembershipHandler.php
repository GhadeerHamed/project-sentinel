<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Handler;

use Sentinel\IdentityAccess\Application\Command\SuspendMembershipCommand;
use Sentinel\IdentityAccess\Application\Policy\MembershipSuspensionPolicy;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
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

final readonly class SuspendMembershipHandler
{
    public function __construct(private MembershipRepository $memberships, private MembershipSuspensionPolicy $policy, private TenantContext $tenantContext, private UuidV7Generator $uuidGenerator, private Clock $clock, private TransactionManager $transactions, private OutboxMessageStore $outbox, private AuditRecorder $audit) {}

    public function handle(SuspendMembershipCommand $command): void
    {
        $this->transactions->transactional(function () use ($command): void {
            if (! $this->policy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to suspend members.');
            }
            $now = $this->clock->now();
            $membership = $this->memberships->getById($command->membershipId(), $this->tenantContext->organizationId())->suspend($now, $this->tenantContext->actingIdentity()->identityId(), $this->uuidGenerator->generate(), $this->tenantContext->correlationId()->value(), $this->tenantContext->causationId()?->value());
            $this->memberships->save($membership, $command->expectedVersion());
            /** @var list<AggregateDomainEvent> $events */
            $events = $membership->pullDomainEvents();
            foreach ($events as $event) {
                $this->outbox->append(OutboxMessage::fromDomainEvent($this->uuidGenerator->generate(), $event, $now));
            }
            $this->audit->record(AuditRecord::fromValues(id: $this->uuidGenerator->generate(), organizationId: $this->tenantContext->organizationId()->value(), actorType: $this->tenantContext->actingIdentity()->identityType(), actorId: $this->tenantContext->actingIdentity()->identityId(), action: 'membership.suspend', resourceType: 'membership', resourceId: $membership->id()->value(), occurredAt: $now, recordedAt: $now, correlationId: $this->tenantContext->correlationId()->value(), causationId: $this->tenantContext->causationId()?->value()));
        });
    }
}
