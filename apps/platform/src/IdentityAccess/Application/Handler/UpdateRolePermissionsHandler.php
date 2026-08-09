<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Application\Handler;

use Sentinel\IdentityAccess\Application\Authorization\AdministrativeAccessInvariant;
use Sentinel\IdentityAccess\Application\Command\UpdateRolePermissionsCommand;
use Sentinel\IdentityAccess\Application\Policy\RolePermissionUpdatePolicy;
use Sentinel\IdentityAccess\Domain\Event\RolePermissionsUpdated;
use Sentinel\IdentityAccess\Domain\Exception\AdministrativeAccessInvariantViolation;
use Sentinel\IdentityAccess\Domain\Exception\RoleNotFound;
use Sentinel\IdentityAccess\Domain\Repository\OrganizationRoleRepository;
use Sentinel\IdentityAccess\Domain\Repository\PermissionAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class UpdateRolePermissionsHandler
{
    public function __construct(
        private OrganizationRoleRepository $roles,
        private PermissionAssignmentRepository $permissions,
        private RolePermissionUpdatePolicy $policy,
        private TenantContext $tenantContext,
        private UuidV7Generator $uuidGenerator,
        private Clock $clock,
        private TransactionManager $transactions,
        private TenantScopedOperationRecorder $operationRecorder,
        private AdministrativeAccessInvariant $administrativeAccessInvariant,
    ) {}

    public function handle(UpdateRolePermissionsCommand $command): void
    {
        $this->transactions->transactional(function () use ($command): void {
            $this->administrativeAccessInvariant->lockOrganizationMutation($this->tenantContext->organizationId());
            $this->assertValidPermissionChange($command);

            if (! $this->policy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to update role permissions.');
            }

            try {
                $role = $this->roles->getById($command->roleId(), $this->tenantContext->organizationId());
            } catch (RoleNotFound) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::NOT_FOUND, 'The role is not available.');
            }

            $now = $this->clock->now();
            $actorIdentityId = IdentityId::fromString($this->tenantContext->actingIdentity()->identityId());
            foreach ($command->grants() as $permission) {
                $this->permissions->assignToRole($this->tenantContext->organizationId(), $role->id(), $permission, $actorIdentityId, $now);
            }
            foreach ($command->revocations() as $permission) {
                $this->permissions->revokeFromRole($this->tenantContext->organizationId(), $role->id(), $permission, $actorIdentityId, $now);
            }

            try {
                $this->administrativeAccessInvariant->assertRetained($this->tenantContext->organizationId(), $now);
            } catch (AdministrativeAccessInvariantViolation) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::CONFLICT, 'The operation would remove the final administrative recovery capability.');
            }

            $event = RolePermissionsUpdated::fromValues(
                eventId: $this->uuidGenerator->generate(),
                organizationId: $this->tenantContext->organizationId()->value(),
                occurredAt: $now,
                correlationId: $this->tenantContext->correlationId()->value(),
                causationId: $this->tenantContext->causationId()?->value(),
                roleId: $role->id()->value(),
                actorIdentityId: $actorIdentityId->value(),
                grantedPermissions: $command->grants(),
                revokedPermissions: $command->revocations(),
            );
            $this->operationRecorder->record([$event], 'role.permissions.update', 'role', $role->id()->value(), $now);
        });
    }

    private function assertValidPermissionChange(UpdateRolePermissionsCommand $command): void
    {
        $permissions = [...$command->grants(), ...$command->revocations()];
        if ($permissions === []) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::VALIDATION_ERROR, 'At least one role permission change is required.');
        }

        $permissionCodes = array_map(static fn (PermissionCode $permission): string => $permission->value(), $permissions);
        if (count($permissionCodes) !== count(array_unique($permissionCodes))) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::VALIDATION_ERROR, 'Role permission changes must not contain duplicate permissions.');
        }

        if (array_any($command->grants(), static fn (PermissionCode $grant): bool => array_any($command->revocations(), static fn (PermissionCode $revocation): bool => $grant->equals($revocation)))) {
            throw ApplicationOperationFailure::forCategory(ErrorCategory::VALIDATION_ERROR, 'A permission cannot be granted and revoked in the same operation.');
        }

        foreach ($permissions as $permission) {
            if (! array_any(CorePermissions::all(), static fn (PermissionCode $corePermission): bool => $corePermission->equals($permission))) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::VALIDATION_ERROR, 'Only supported Core permissions may be assigned to a role.');
            }
        }
    }
}
