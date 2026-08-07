<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Handler;

use Sentinel\IdentityAccess\Application\Command\AcceptMembershipInvitationCommand;
use Sentinel\IdentityAccess\Domain\Exception\InvalidMembershipTransition;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class AcceptMembershipInvitationHandler
{
    public function __construct(private MembershipRepository $memberships, private UuidV7Generator $uuidGenerator, private Clock $clock, private TransactionManager $transactions, private OutboxMessageStore $outbox, private AuditRecorder $audit) {}

    public function handle(AcceptMembershipInvitationCommand $command): ResourceVersion
    {
        return $this->transactions->transactional(function () use ($command): ResourceVersion {
            try {
                $membership = $this->memberships->getById($command->membershipId, $command->organizationId);
            } catch (MembershipNotFound) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::NOT_FOUND, 'The membership is not available.');
            }
            if (! $membership->userIdentityId()->equals($command->identityId)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The authenticated identity does not own this invitation.');
            }
            $now = $this->clock->now();
            try {
                $membership = $membership->acceptInvitation($now, $command->identityId->value(), $this->uuidGenerator->generate(), $command->correlationId);
            } catch (InvalidMembershipTransition) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::VALIDATION_ERROR, 'The membership invitation cannot be accepted.');
            }
            $this->memberships->save($membership, $command->expectedVersion);
            /** @var list<AggregateDomainEvent> $events */ $events = $membership->pullDomainEvents();
            foreach ($events as $event) {
                $this->outbox->append(OutboxMessage::fromDomainEvent($this->uuidGenerator->generate(), $event, $now));
            }
            $this->audit->record(AuditRecord::fromValues(id: $this->uuidGenerator->generate(), organizationId: $command->organizationId->value(), actorType: 'human_user', actorId: $command->identityId->value(), action: 'membership.invitation_accept', resourceType: 'membership', resourceId: $membership->id()->value(), occurredAt: $now, recordedAt: $now, correlationId: $command->correlationId, causationId: null));

            return $membership->version();
        });
    }
}
