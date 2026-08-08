<?php

declare(strict_types=1);

namespace Tests\Application\IdentityAccess;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Application\Command\AssignRoleCommand;
use Sentinel\IdentityAccess\Application\Handler\AssignRoleHandler;
use Sentinel\IdentityAccess\Application\Policy\RoleAssignmentPolicy;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Entity\OrganizationRole;
use Sentinel\IdentityAccess\Domain\Entity\RoleAssignment;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\OrganizationRoleRepository;
use Sentinel\IdentityAccess\Domain\Repository\RoleAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final class AssignRoleHandlerTest extends TestCase
{
    #[Test]
    public function it_assigns_an_organization_role_with_an_event_and_audit_record(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c076001';
        $actorIdentityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c076002';
        $memberIdentityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c076003';
        $membershipId = '01890f7a-5b7c-7cc0-98c4-dc0c0c076004';
        $roleId = '01890f7a-5b7c-7cc0-98c4-dc0c0c076005';
        $assignmentId = '01890f7a-5b7c-7cc0-98c4-dc0c0c076006';
        $at = new DateTimeImmutable('2026-08-08T12:00:00+00:00');
        $context = TenantContext::fromValues($organizationId, $actorIdentityId, 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c076007');
        $memberships = $this->createMock(MembershipRepository::class);
        $memberships->method('getById')->willReturn(Membership::fromValues($membershipId, $organizationId, $memberIdentityId, 'active', $at, $at, $at));
        $roles = $this->createMock(OrganizationRoleRepository::class);
        $roles->method('getById')->willReturn(OrganizationRole::fromValues($roleId, $organizationId, 'operator'));
        $assignments = $this->createMock(RoleAssignmentRepository::class);
        $assignments->method('activeForIdentity')->willReturn([]);
        $assignments->expects(self::once())->method('assign')->with(self::callback(static fn (RoleAssignment $assignment): bool => $assignment->id()->value() === $assignmentId && $assignment->identityId()->value() === $memberIdentityId && $assignment->roleId()->value() === $roleId));
        $policy = $this->createMock(RoleAssignmentPolicy::class);
        $policy->expects(self::once())->method('allows')->with($context)->willReturn(true);
        $ids = $this->createMock(UuidV7Generator::class);
        $ids->method('generate')->willReturnOnConsecutiveCalls($assignmentId, '01890f7a-5b7c-7cc0-98c4-dc0c0c076008', '01890f7a-5b7c-7cc0-98c4-dc0c0c076009', '01890f7a-5b7c-7cc0-98c4-dc0c0c076010');
        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn($at);
        $outbox = $this->createMock(OutboxMessageStore::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn ($message): bool => $message->jsonSerialize()['event_type'] === 'role.assigned'));
        $audit = $this->createMock(AuditRecorder::class);
        $audit->expects(self::once())->method('record')->with(self::callback(static fn ($record): bool => $record->action() === 'role.assign' && $record->resource()->type() === 'role_assignment'));
        $transactions = $this->createMock(TransactionManager::class);
        $transactions->method('transactional')->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $recorder = new TenantScopedOperationRecorder($context, $ids, $outbox, $audit);

        $result = (new AssignRoleHandler($memberships, $roles, $assignments, $policy, $context, $ids, $clock, $transactions, $recorder))->handle(new AssignRoleCommand(MembershipId::fromString($membershipId), RoleId::fromString($roleId)));

        self::assertSame($assignmentId, $result->value());
    }
}
