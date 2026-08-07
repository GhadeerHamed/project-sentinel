<?php

declare(strict_types=1);

namespace Tests\Application\Organization;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Organization\Application\Command\UpdateOrganizationCommand;
use Sentinel\Organization\Application\Handler\UpdateOrganizationHandler;
use Sentinel\Organization\Application\Policy\OrganizationUpdatePolicy;
use Sentinel\Organization\Domain\Entity\Organization;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final class UpdateOrganizationHandlerTest extends TestCase
{
    #[Test]
    public function it_updates_the_tenant_organization_with_an_event_and_audit_record(): void
    {
        $organization = Organization::fromValues('01890f7a-5b7c-7cc0-98c4-dc0c0c075001', 'Acme', 'acme', 'active', '01890f7a-5b7c-7cc0-98c4-dc0c0c075002', new DateTimeImmutable('2026-08-01T00:00:00+00:00'), new DateTimeImmutable('2026-08-01T00:00:00+00:00'), 3);
        $repository = $this->createMock(OrganizationRepository::class);
        $repository->method('getById')->willReturn($organization);
        $repository->expects(self::once())->method('save')->with(self::callback(static fn (Organization $updated): bool => $updated->name() === 'Acme Operations' && $updated->timezone() === 'Europe/Bucharest' && $updated->version()->value() === 4), self::callback(static fn (ExpectedVersion $version): bool => $version->value()?->value() === 3));
        $policy = $this->createMock(OrganizationUpdatePolicy::class);
        $policy->expects(self::once())->method('allows')->willReturn(true);
        $outbox = $this->createMock(OutboxMessageStore::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn ($message): bool => $message->jsonSerialize()['event_type'] === 'organization.updated'));
        $audit = $this->createMock(AuditRecorder::class);
        $audit->expects(self::once())->method('record')->with(self::callback(static fn ($record): bool => $record->action() === 'organization.update'));
        $transaction = $this->createMock(TransactionManager::class);
        $transaction->method('transactional')->willReturnCallback(static fn (callable $operation): mixed => $operation());
        $clock = $this->createMock(Clock::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-08-02T00:00:00+00:00'));
        $ids = $this->createMock(UuidV7Generator::class);
        $ids->method('generate')->willReturn('01890f7a-5b7c-7cc0-98c4-dc0c0c075003');
        $context = TenantContext::fromValues('01890f7a-5b7c-7cc0-98c4-dc0c0c075001', '01890f7a-5b7c-7cc0-98c4-dc0c0c075002', 'user', '01890f7a-5b7c-7cc0-98c4-dc0c0c075004');

        $result = (new UpdateOrganizationHandler($repository, $policy, $context, $ids, $clock, $transaction, $outbox, $audit))->handle(new UpdateOrganizationCommand('Acme Operations', 'Europe/Bucharest', ExpectedVersion::fromInt(3)));

        self::assertSame('Acme Operations', $result->name());
        self::assertSame(4, $result->version()->value());
    }
}
