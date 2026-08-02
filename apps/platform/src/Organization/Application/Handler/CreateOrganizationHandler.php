<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Handler;

use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\UserRepository;
use Sentinel\Organization\Application\Command\CreateOrganizationCommand;
use Sentinel\Organization\Application\Policy\OrganizationCreationPolicy;
use Sentinel\Organization\Application\Result\CreateOrganizationResult;
use Sentinel\Organization\Domain\Entity\Organization;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\DomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class CreateOrganizationHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository,
        private MembershipRepository $membershipRepository,
        private OrganizationCreationPolicy $organizationCreationPolicy,
        private UuidV7Generator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactionManager,
        private OutboxMessageStore $outboxMessageStore,
        private AuditRecorder $auditRecorder,
    ) {}

    public function handle(CreateOrganizationCommand $command): CreateOrganizationResult
    {
        return $this->transactionManager->transactional(function () use ($command): CreateOrganizationResult {
            $creator = $command->creator();
            $creatorUser = $this->userRepository->getByIdentityId(IdentityId::fromString($creator->identityId()));

            if (! $creatorUser->canAuthenticate()) {
                throw ApplicationOperationFailure::forCategory(
                    ErrorCategory::INVALID_STATE,
                    'Creator identity is not eligible to create organizations.',
                    ['identity_id' => $creator->identityId()],
                );
            }

            if (! $this->organizationCreationPolicy->allows($creator)) {
                throw ApplicationOperationFailure::forCategory(
                    ErrorCategory::FORBIDDEN,
                    'Creator is not authorized to create organizations.',
                    ['identity_id' => $creator->identityId()],
                );
            }

            $now = $this->clock->now();
            $organizationId = $this->uuidGenerator->generate();
            $membershipId = $this->uuidGenerator->generate();

            $organization = Organization::create(
                id: $organizationId,
                name: $command->organizationName(),
                slug: $command->organizationSlug(),
                createdByIdentityId: $creator->identityId(),
                at: $now,
                eventId: $this->uuidGenerator->generate(),
                correlationId: $command->correlationId()->value(),
                causationId: $command->causationId()?->value(),
            );

            $membership = Membership::invite(
                id: $membershipId,
                organizationId: $organizationId,
                userIdentityId: $creator->identityId(),
                actorIdentityId: $creator->identityId(),
                at: $now,
                eventId: $this->uuidGenerator->generate(),
                correlationId: $command->correlationId()->value(),
                causationId: $command->causationId()?->value(),
            )->activate(
                at: $now,
                actorIdentityId: $creator->identityId(),
                eventId: $this->uuidGenerator->generate(),
                correlationId: $command->correlationId()->value(),
                causationId: $command->causationId()?->value(),
            );

            $this->organizationRepository->save($organization, ExpectedVersion::none());
            $this->membershipRepository->save($membership, ExpectedVersion::none());

            $events = [...$organization->pullDomainEvents(), ...$membership->pullDomainEvents()];

            foreach ($events as $event) {
                [$aggregateType, $aggregateId, $aggregateVersion] = $this->aggregateMetadata(
                    $event,
                    $organization,
                    $membership,
                );

                $this->outboxMessageStore->append(OutboxMessage::fromDomainEvent(
                    id: $this->uuidGenerator->generate(),
                    event: $event,
                    aggregateType: $aggregateType,
                    aggregateId: $aggregateId,
                    aggregateVersion: $aggregateVersion,
                    createdAt: $now,
                ));
            }

            $this->auditRecorder->record(AuditRecord::fromValues(
                id: $this->uuidGenerator->generate(),
                organizationId: $organization->id()->value(),
                actorType: $creator->identityType(),
                actorId: $creator->identityId(),
                action: 'organization.create',
                resourceType: 'organization',
                resourceId: $organization->id()->value(),
                occurredAt: $now,
                recordedAt: $now,
                correlationId: $command->correlationId()->value(),
                causationId: $command->causationId()?->value(),
            ));

            return CreateOrganizationResult::fromValues(
                organizationId: $organization->id()->value(),
                creatorMembershipId: $membership->id()->value(),
            );
        });
    }

    /**
     * @return array{string, string, int}
     */
    private function aggregateMetadata(
        DomainEvent $event,
        Organization $organization,
        Membership $membership,
    ): array {
        if (str_starts_with($event->eventName(), 'organization.')) {
            return [
                'organization',
                $organization->id()->value(),
                $organization->version()->value(),
            ];
        }

        if (str_starts_with($event->eventName(), 'membership.')) {
            return [
                'membership',
                $membership->id()->value(),
                $membership->version()->value(),
            ];
        }

        throw ApplicationOperationFailure::forCategory(
            ErrorCategory::INVALID_STATE,
            sprintf('Unsupported lifecycle event for organization creation: %s', $event->eventName()),
        );
    }
}
