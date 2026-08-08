<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Organization\Domain\Event\OrganizationUpdated;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class TenantScopedOperationRecorderTest extends TestCase
{
    #[Test]
    public function it_records_events_and_audit_metadata_from_the_tenant_context(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075001';
        $identityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075002';
        $correlationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075003';
        $at = new DateTimeImmutable('2026-08-02T00:00:00+00:00');
        $event = OrganizationUpdated::fromValues('01890f7a-5b7c-7cc0-98c4-dc0c0c075004', $organizationId, $at, $correlationId, null, $identityId, 'Acme Operations', 'Europe/Bucharest', 4);
        $context = TenantContext::fromValues($organizationId, $identityId, 'user', $correlationId);
        $ids = $this->createMock(UuidV7Generator::class);
        $ids->method('generate')->willReturn('01890f7a-5b7c-7cc0-98c4-dc0c0c075005');
        $outbox = $this->createMock(OutboxMessageStore::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn (OutboxMessage $message): bool => $message->jsonSerialize()['event_type'] === 'organization.updated' && $message->jsonSerialize()['organization_id'] === $organizationId));
        $audit = $this->createMock(AuditRecorder::class);
        $audit->expects(self::once())->method('record')->with(self::callback(static fn (AuditRecord $record): bool => $record->organizationId()?->value() === $organizationId && $record->actor()->type() === 'user' && $record->actor()->id()?->value() === $identityId && $record->action() === 'organization.update' && $record->resource()->type() === 'organization' && $record->resource()->id()?->value() === $organizationId && $record->correlationId()->value() === $correlationId && $record->occurredAt() == $at && $record->recordedAt() == $at));

        (new TenantScopedOperationRecorder($context, $ids, $outbox, $audit))->record([$event], 'organization.update', 'organization', $organizationId, $at);
    }
}
