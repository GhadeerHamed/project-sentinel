<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Handler;

use Sentinel\IdentityAccess\Application\Command\AssignRoleCommand;
use Sentinel\IdentityAccess\Application\Policy\RoleAssignmentPolicy;
use Sentinel\IdentityAccess\Domain\Entity\RoleAssignment;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Exception\RoleNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\OrganizationRoleRepository;
use Sentinel\IdentityAccess\Domain\Repository\RoleAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class AssignRoleHandler
{
    public function __construct(
        private MembershipRepository $memberships,
        private OrganizationRoleRepository $roles,
        private RoleAssignmentRepository $assignments,
        private RoleAssignmentPolicy $policy,
        private TenantContext $tenantContext,
        private UuidV7Generator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
        private TenantScopedOperationRecorder $operationRecorder,
    ) {}

    public function handle(AssignRoleCommand $command): RoleAssignmentId
    {
        return $this->transactions->transactional(function () use ($command): RoleAssignmentId {
            if (! $this->policy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to assign roles.');
            }

            try {
                $membership = $this->memberships->getById($command->membershipId(), $this->tenantContext->organizationId());
            } catch (MembershipNotFound) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::NOT_FOUND, 'The membership is not available.');
            }

            try {
                $role = $this->roles->getById($command->roleId(), $this->tenantContext->organizationId());
            } catch (RoleNotFound) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::NOT_FOUND, 'The role is not available.');
            }

            $now = $this->clock->now();
            foreach ($this->assignments->activeForIdentity($this->tenantContext->organizationId(), $membership->userIdentityId(), $now) as $existingAssignment) {
                if ($existingAssignment->roleId()->equals($role->id())) {
                    throw ApplicationOperationFailure::forCategory(ErrorCategory::CONFLICT, 'The identity already has this active role assignment in the organization.');
                }
            }

            $assignment = RoleAssignment::assign(
                id: $this->uuidGenerator->generate(),
                organizationId: $this->tenantContext->organizationId()->value(),
                identityId: $membership->userIdentityId()->value(),
                roleId: $role->id()->value(),
                roleOrganizationId: $role->organizationId()->value(),
                assignedByIdentityId: $this->tenantContext->actingIdentity()->identityId(),
                assignedAt: $now,
                eventId: $this->uuidGenerator->generate(),
                correlationId: $this->tenantContext->correlationId()->value(),
                causationId: $this->tenantContext->causationId()?->value(),
            );
            $this->assignments->assign($assignment);
            /** @var list<AggregateDomainEvent> $events */
            $events = $assignment->pullDomainEvents();
            $this->operationRecorder->record($events, 'role.assign', 'role_assignment', $assignment->id()->value(), $now);

            return $assignment->id();
        });
    }
}
