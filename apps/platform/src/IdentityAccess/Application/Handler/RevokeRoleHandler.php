<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Handler;

use Sentinel\IdentityAccess\Application\Authorization\AdministrativeAccessInvariant;
use Sentinel\IdentityAccess\Application\Command\RevokeRoleCommand;
use Sentinel\IdentityAccess\Application\Policy\RoleRevocationPolicy;
use Sentinel\IdentityAccess\Domain\Exception\AdministrativeAccessInvariantViolation;
use Sentinel\IdentityAccess\Domain\Exception\InvalidRoleAssignmentTransition;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Exception\RoleAssignmentNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\RoleAssignmentRepository;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class RevokeRoleHandler
{
    public function __construct(
        private RoleAssignmentRepository $assignments,
        private MembershipRepository $memberships,
        private RoleRevocationPolicy $policy,
        private TenantContext $tenantContext,
        private UuidV7Generator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
        private TenantScopedOperationRecorder $operationRecorder,
        private AdministrativeAccessInvariant $administrativeAccessInvariant,
    ) {}

    public function handle(RevokeRoleCommand $command): void
    {
        $this->transactions->transactional(function () use ($command): void {
            $this->administrativeAccessInvariant->lockOrganizationMutation($this->tenantContext->organizationId());

            if (! $this->policy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to revoke roles.');
            }

            try {
                $assignment = $this->assignments->getById($command->assignmentId(), $this->tenantContext->organizationId());
                $this->memberships->getByIdentity($this->tenantContext->organizationId(), $assignment->identityId());
            } catch (RoleAssignmentNotFound|MembershipNotFound) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::NOT_FOUND, 'The role assignment is not available.');
            }

            $now = $this->clock->now();
            try {
                $revoked = $assignment->revokeWithEvent(
                    revokedAt: $now,
                    revokedByIdentityId: $this->tenantContext->actingIdentity()->identityId(),
                    eventId: $this->uuidGenerator->generate(),
                    correlationId: $this->tenantContext->correlationId()->value(),
                    causationId: $this->tenantContext->causationId()?->value(),
                );
            } catch (InvalidRoleAssignmentTransition) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::CONFLICT, 'The role assignment has already been revoked.');
            }
            $this->assignments->revoke(
                assignmentId: $revoked->id(),
                organizationId: $this->tenantContext->organizationId(),
                revokedBy: IdentityId::fromString($this->tenantContext->actingIdentity()->identityId()),
                revokedAt: $now,
            );
            try {
                $this->administrativeAccessInvariant->assertRetained($this->tenantContext->organizationId(), $now);
            } catch (AdministrativeAccessInvariantViolation) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::CONFLICT, 'The operation would remove the final administrative recovery capability.');
            }
            /** @var list<AggregateDomainEvent> $events */
            $events = $revoked->pullDomainEvents();
            $this->operationRecorder->record($events, 'role.revoke', 'role_assignment', $revoked->id()->value(), $now);
        });
    }
}
