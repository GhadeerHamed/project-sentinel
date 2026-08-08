<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Handler;

use Sentinel\IdentityAccess\Application\Command\RemoveMembershipCommand;
use Sentinel\IdentityAccess\Application\Policy\MembershipRemovalPolicy;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class RemoveMembershipHandler
{
    public function __construct(private MembershipRepository $memberships, private MembershipRemovalPolicy $policy, private TenantContext $tenantContext, private UuidV7Generator $uuidGenerator, private Clock $clock, private TransactionManager $transactions, private TenantScopedOperationRecorder $operationRecorder) {}

    public function handle(RemoveMembershipCommand $command): ResourceVersion
    {
        return $this->transactions->transactional(function () use ($command): ResourceVersion {
            if (! $this->policy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to remove members.');
            }

            $now = $this->clock->now();
            try {
                $membership = $this->memberships->getById($command->membershipId(), $this->tenantContext->organizationId());
            } catch (MembershipNotFound) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::NOT_FOUND, 'The membership is not available.');
            }
            $membership = $membership->remove($now, $this->tenantContext->actingIdentity()->identityId(), $this->uuidGenerator->generate(), $this->tenantContext->correlationId()->value(), $this->tenantContext->causationId()?->value());
            $this->memberships->save($membership, $command->expectedVersion());
            /** @var list<AggregateDomainEvent> $events */
            $events = $membership->pullDomainEvents();
            $this->operationRecorder->record($events, 'membership.remove', 'membership', $membership->id()->value(), $now);

            return $membership->version();
        });
    }
}
