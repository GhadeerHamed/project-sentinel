<?php

declare(strict_types=1);

namespace Tests\Application\IdentityAccess;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Application\Authorization\AdministrativeAccessInvariant;
use Sentinel\IdentityAccess\Application\Command\UpdateRolePermissionsCommand;
use Sentinel\IdentityAccess\Application\Handler\UpdateRolePermissionsHandler;
use Sentinel\IdentityAccess\Application\Policy\RolePermissionUpdatePolicy;
use Sentinel\IdentityAccess\Domain\Entity\OrganizationRole;
use Sentinel\IdentityAccess\Domain\Repository\AdministrativeAccessRepository;
use Sentinel\IdentityAccess\Domain\Repository\OrganizationRoleRepository;
use Sentinel\IdentityAccess\Domain\Repository\PermissionAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final class UpdateRolePermissionsHandlerTest extends TestCase
{
    #[Test]
    public function it_updates_tenant_role_permissions_with_an_event_and_audit_record(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c078001';
        $actorIdentityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c078002';
        $roleId = '01890f7a-5b7c-7cc0-98c4-dc0c0c078003';
        $at = new DateTimeImmutable('2026-08-09T12:00:00+00:00');
        $context = TenantContext::fromValues($organizationId, $actorIdentityId, 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c078004');
        $roles = $this->createMock(OrganizationRoleRepository::class);
        $roles->expects(self::once())->method('getById')->with(RoleId::fromString($roleId), $context->organizationId())->willReturn(OrganizationRole::fromValues($roleId, $organizationId, 'operator'));
        $permissions = $this->createMock(PermissionAssignmentRepository::class);
        $permissions->expects(self::once())->method('assignToRole')->with($context->organizationId(), RoleId::fromString($roleId), CorePermissions::updateOrganization(), self::anything(), $at);
        $permissions->expects(self::once())->method('revokeFromRole')->with($context->organizationId(), RoleId::fromString($roleId), CorePermissions::suspendOrganization(), self::anything(), $at);
        $policy = $this->createMock(RolePermissionUpdatePolicy::class);
        $policy->expects(self::once())->method('allows')->with($context)->willReturn(true);
        $ids = $this->createMock(UuidV7Generator::class);
        $ids->method('generate')->willReturnOnConsecutiveCalls('01890f7a-5b7c-7cc0-98c4-dc0c0c078005', '01890f7a-5b7c-7cc0-98c4-dc0c0c078006', '01890f7a-5b7c-7cc0-98c4-dc0c0c078007');
        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn($at);
        $outbox = $this->createMock(OutboxMessageStore::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn ($message): bool => $message->jsonSerialize()['event_type'] === 'role.permissions_updated'));
        $audit = $this->createMock(AuditRecorder::class);
        $audit->expects(self::once())->method('record')->with(self::callback(static fn ($record): bool => $record->action() === 'role.permissions.update' && $record->resource()->id()?->value() === $roleId));
        $transactions = $this->createMock(TransactionManager::class);
        $transactions->method('transactional')->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $administrativeAccess = $this->createMock(AdministrativeAccessRepository::class);
        $administrativeAccess->expects(self::once())->method('lockOrganizationForAdministrativeAccessMutation')->with($context->organizationId());
        $administrativeAccess->expects(self::once())->method('hasActiveHumanIdentityWithEffectivePermission')->with($context->organizationId(), CorePermissions::manageOrganizationMembers(), $at)->willReturn(true);

        (new UpdateRolePermissionsHandler(
            $roles,
            $permissions,
            $policy,
            $context,
            $ids,
            $clock,
            $transactions,
            new TenantScopedOperationRecorder($context, $ids, $outbox, $audit),
            new AdministrativeAccessInvariant($administrativeAccess),
        ))->handle(new UpdateRolePermissionsCommand(
            RoleId::fromString($roleId),
            [CorePermissions::updateOrganization()],
            [CorePermissions::suspendOrganization()],
        ));
    }

    #[Test]
    public function it_rejects_permission_codes_outside_the_core_catalog(): void
    {
        $context = TenantContext::fromValues('01890f7a-5b7c-7cc0-98c4-dc0c0c078011', '01890f7a-5b7c-7cc0-98c4-dc0c0c078012', 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c078013');
        $transactions = $this->createMock(TransactionManager::class);
        $transactions->method('transactional')->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $administrativeAccess = $this->createMock(AdministrativeAccessRepository::class);
        $administrativeAccess->expects(self::once())->method('lockOrganizationForAdministrativeAccessMutation')->with($context->organizationId());

        $this->expectExceptionMessage('Only supported Core permissions may be assigned to a role.');

        (new UpdateRolePermissionsHandler(
            $this->createMock(OrganizationRoleRepository::class),
            $this->createMock(PermissionAssignmentRepository::class),
            $this->createMock(RolePermissionUpdatePolicy::class),
            $context,
            $this->createMock(UuidV7Generator::class),
            $this->createMock(Clock::class),
            $transactions,
            new TenantScopedOperationRecorder($context, $this->createMock(UuidV7Generator::class), $this->createMock(OutboxMessageStore::class), $this->createMock(AuditRecorder::class)),
            new AdministrativeAccessInvariant($administrativeAccess),
        ))->handle(new UpdateRolePermissionsCommand(
            RoleId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c078014'),
            [PermissionCode::fromString('organization.free_form')],
            [],
        ));
    }
}
