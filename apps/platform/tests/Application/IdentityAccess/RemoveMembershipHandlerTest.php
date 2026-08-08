<?php

declare(strict_types=1);

namespace Tests\Application\IdentityAccess;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Application\Command\RemoveMembershipCommand;
use Sentinel\IdentityAccess\Application\Handler\RemoveMembershipHandler;
use Sentinel\IdentityAccess\Application\Policy\MembershipRemovalPolicy;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final class RemoveMembershipHandlerTest extends TestCase
{
    #[Test]
    public function it_removes_a_tenant_membership_with_an_event_and_audit_record(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075001';
        $identityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075002';
        $membership = Membership::fromValues('01890f7a-5b7c-7cc0-98c4-dc0c0c075003', $organizationId, '01890f7a-5b7c-7cc0-98c4-dc0c0c075004', 'active', new DateTimeImmutable('2026-08-01T00:00:00+00:00'), new DateTimeImmutable('2026-08-01T00:00:00+00:00'), new DateTimeImmutable('2026-08-01T00:00:00+00:00'), version: 3);
        $context = TenantContext::fromValues($organizationId, $identityId, 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c075005');
        $repository = $this->createMock(MembershipRepository::class);
        $repository->method('getById')->willReturn($membership);
        $repository->expects(self::once())->method('save')->with(self::callback(static fn (Membership $removed): bool => $removed->status()->value === 'removed' && $removed->version()->value() === 4), self::callback(static fn (ExpectedVersion $version): bool => $version->value()?->value() === 3));
        $policy = $this->createMock(MembershipRemovalPolicy::class);
        $policy->expects(self::once())->method('allows')->with($context)->willReturn(true);
        $ids = $this->createMock(UuidV7Generator::class);
        $ids->method('generate')->willReturn('01890f7a-5b7c-7cc0-98c4-dc0c0c075006');
        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-08-02T00:00:00+00:00'));
        $outbox = $this->createMock(OutboxMessageStore::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn ($message): bool => $message->jsonSerialize()['event_type'] === 'membership.removed'));
        $audit = $this->createMock(AuditRecorder::class);
        $audit->expects(self::once())->method('record')->with(self::callback(static fn ($record): bool => $record->action() === 'membership.remove' && $record->resource()->id()?->value() === '01890f7a-5b7c-7cc0-98c4-dc0c0c075003'));
        $transactions = $this->createMock(TransactionManager::class);
        $transactions->method('transactional')->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $recorder = new TenantScopedOperationRecorder($context, $ids, $outbox, $audit);

        $version = (new RemoveMembershipHandler($repository, $policy, $context, $ids, $clock, $transactions, $recorder))->handle(new RemoveMembershipCommand($membership->id(), ExpectedVersion::fromInt(3)));

        self::assertSame(4, $version->value());
    }
}
