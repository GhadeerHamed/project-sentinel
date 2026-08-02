<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Audit;

use DateTimeImmutable;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\InvalidAuditRecord;

final class AuditRecordTest extends TestCase
{
    #[Test]
    public function it_creates_an_immutable_audit_record_envelope_with_required_metadata(): void
    {
        $occurredAt = new DateTimeImmutable('2026-08-02T11:00:00+00:00');
        $recordedAt = new DateTimeImmutable('2026-08-02T11:00:01+00:00');

        $record = AuditRecord::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c01',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c02',
            actorType: 'user',
            actorId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c03',
            action: 'membership.removed',
            resourceType: 'membership',
            resourceId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c04',
            occurredAt: $occurredAt,
            recordedAt: $recordedAt,
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c05',
            causationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c06',
        );

        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073c01', $record->id()->value());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073c02', $record->organizationId()?->value());
        self::assertSame('user', $record->actor()->type());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073c03', $record->actor()->id()?->value());
        self::assertSame('membership.removed', $record->action());
        self::assertSame('membership', $record->resource()->type());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073c04', $record->resource()->id()?->value());
        self::assertSame($occurredAt, $record->occurredAt());
        self::assertSame($recordedAt, $record->recordedAt());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073c05', $record->correlationId()->value());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073c06', $record->causationId()?->value());
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function it_serializes_audit_metadata_with_correlation_and_causation_identifiers(): void
    {
        $record = AuditRecord::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c11',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c12',
            actorType: 'agent',
            actorId: null,
            action: 'workflow.action_executed',
            resourceType: 'workflow',
            resourceId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c13',
            occurredAt: new DateTimeImmutable('2026-08-02T11:30:00+00:00'),
            recordedAt: new DateTimeImmutable('2026-08-02T11:30:01+00:00'),
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c14',
            causationId: null,
        );

        self::assertSame(
            '{"id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073c11","organization_id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073c12","actor_type":"agent","actor_id":null,"action":"workflow.action_executed","resource_type":"workflow","resource_id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073c13","occurred_at":"2026-08-02T11:30:00+00:00","recorded_at":"2026-08-02T11:30:01+00:00","correlation_id":"01890f7a-5b7c-7cc0-98c4-dc0c0c073c14","causation_id":null}',
            json_encode($record, JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function it_rejects_invalid_audit_metadata_during_construction(): void
    {
        $this->expectException(InvalidAuditRecord::class);

        AuditRecord::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c21',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c22',
            actorType: 'user',
            actorId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c23',
            action: ' ',
            resourceType: 'membership',
            resourceId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c24',
            occurredAt: new DateTimeImmutable('2026-08-02T12:00:00+00:00'),
            recordedAt: new DateTimeImmutable('2026-08-02T12:00:01+00:00'),
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073c25',
            causationId: null,
        );
    }
}
