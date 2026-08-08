<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Handler;

use Sentinel\IdentityAccess\Application\Command\InviteMembershipCommand;
use Sentinel\IdentityAccess\Application\Policy\MembershipInvitationPolicy;
use Sentinel\IdentityAccess\Application\Result\InviteMembershipResult;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class InviteMembershipHandler
{
    public function __construct(
        private MembershipRepository $memberships,
        private MembershipInvitationPolicy $policy,
        private TenantContext $tenantContext,
        private UuidV7Generator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
        private TenantScopedOperationRecorder $operationRecorder,
    ) {}

    public function handle(InviteMembershipCommand $command): InviteMembershipResult
    {
        return $this->transactions->transactional(function () use ($command): InviteMembershipResult {
            if (! $this->policy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to invite members.');
            }

            try {
                $this->memberships->getByIdentity($this->tenantContext->organizationId(), $command->memberIdentityId());
            } catch (MembershipNotFound) {
                $now = $this->clock->now();
                $membership = Membership::invite(
                    id: $this->uuidGenerator->generate(), organizationId: $this->tenantContext->organizationId()->value(), userIdentityId: $command->memberIdentityId()->value(), actorIdentityId: $this->tenantContext->actingIdentity()->identityId(), at: $now, eventId: $this->uuidGenerator->generate(), correlationId: $this->tenantContext->correlationId()->value(), causationId: $this->tenantContext->causationId()?->value(),
                );
                $this->memberships->save($membership, ExpectedVersion::none());
                /** @var list<AggregateDomainEvent> $events */
                $events = $membership->pullDomainEvents();
                $this->operationRecorder->record($events, 'membership.invite', 'membership', $membership->id()->value(), $now);

                return new InviteMembershipResult($membership->id(), $membership->status());
            }

            throw ApplicationOperationFailure::forCategory(ErrorCategory::CONFLICT, 'The identity already has a membership in this organization.');
        });
    }
}
