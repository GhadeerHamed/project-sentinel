<?php

declare(strict_types=1);

namespace Tests\Application\IdentityAccess;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Application\Command\RevokeRoleCommand;
use Sentinel\IdentityAccess\Application\Handler\RevokeRoleHandler;
use Sentinel\IdentityAccess\Application\Policy\RoleRevocationPolicy;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Entity\RoleAssignment;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\RoleAssignmentRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final class RevokeRoleHandlerTest extends TestCase
{
    #[Test]
    public function it_revokes_a_tenant_role_assignment_with_an_event_and_audit_record(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c077001';
        $actorId = '01890f7a-5b7c-7cc0-98c4-dc0c0c077002';
        $identityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c077003';
        $assignmentId = '01890f7a-5b7c-7cc0-98c4-dc0c0c077004';
        $at = new DateTimeImmutable('2026-08-08T12:00:00+00:00');
        $context = TenantContext::fromValues($organizationId, $actorId, 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c077005');
        $assignment = RoleAssignment::fromValues($assignmentId, $organizationId, $identityId, '01890f7a-5b7c-7cc0-98c4-dc0c0c077006', $organizationId, $actorId, $at);
        $assignments = $this->createMock(RoleAssignmentRepository::class);
        $assignments->method('getById')->willReturn($assignment);
        $assignments->expects(self::once())->method('revoke');
        $memberships = $this->createMock(MembershipRepository::class);
        $memberships->method('getByIdentity')->willReturn(Membership::fromValues('01890f7a-5b7c-7cc0-98c4-dc0c0c077007', $organizationId, $identityId, 'active', $at, $at, $at));
        $policy = $this->createMock(RoleRevocationPolicy::class);
        $policy->method('allows')->willReturn(true);
        $ids = $this->createMock(UuidV7Generator::class);
        $ids->method('generate')->willReturnOnConsecutiveCalls('01890f7a-5b7c-7cc0-98c4-dc0c0c077008', '01890f7a-5b7c-7cc0-98c4-dc0c0c077009', '01890f7a-5b7c-7cc0-98c4-dc0c0c077010');
        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn($at);
        $outbox = $this->createMock(OutboxMessageStore::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn ($message): bool => $message->jsonSerialize()['event_type'] === 'role.revoked'));
        $audit = $this->createMock(AuditRecorder::class);
        $audit->expects(self::once())->method('record')->with(self::callback(static fn ($record): bool => $record->action() === 'role.revoke'));
        $transactions = $this->createMock(TransactionManager::class);
        $transactions->method('transactional')->willReturnCallback(static fn (callable $operation): mixed => $operation());

        (new RevokeRoleHandler($assignments, $memberships, $policy, $context, $ids, $clock, $transactions, new TenantScopedOperationRecorder($context, $ids, $outbox, $audit)))->handle(new RevokeRoleCommand(RoleAssignmentId::fromString($assignmentId)));
    }
}
