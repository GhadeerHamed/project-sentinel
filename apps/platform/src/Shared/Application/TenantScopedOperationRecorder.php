<?php

declare(strict_types=1);

namespace Sentinel\Shared\Application;

use DateTimeImmutable;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final readonly class TenantScopedOperationRecorder
{
    public function __construct(
        private TenantContext $tenantContext,
        private UuidV7Generator $uuidGenerator,
        private OutboxMessageStore $outbox,
        private AuditRecorder $audit,
    ) {}

    /**
     * @param  list<AggregateDomainEvent>  $events
     */
    public function record(
        array $events,
        string $action,
        string $resourceType,
        string $resourceId,
        DateTimeImmutable $at,
    ): void {
        foreach ($events as $event) {
            $this->outbox->append(OutboxMessage::fromDomainEvent($this->uuidGenerator->generate(), $event, $at));
        }

        $this->audit->record(AuditRecord::fromValues(
            id: $this->uuidGenerator->generate(),
            organizationId: $this->tenantContext->organizationId()->value(),
            actorType: $this->tenantContext->actingIdentity()->identityType(),
            actorId: $this->tenantContext->actingIdentity()->identityId(),
            action: $action,
            resourceType: $resourceType,
            resourceId: $resourceId,
            occurredAt: $at,
            recordedAt: $at,
            correlationId: $this->tenantContext->correlationId()->value(),
            causationId: $this->tenantContext->causationId()?->value(),
        ));
    }
}
