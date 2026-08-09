<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Handler;

use Sentinel\Organization\Application\Command\SuspendOrganizationCommand;
use Sentinel\Organization\Application\Policy\OrganizationSuspensionPolicy;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class SuspendOrganizationHandler
{
    public function __construct(private OrganizationRepository $organizations, private OrganizationSuspensionPolicy $policy, private TenantContext $tenantContext, private UuidV7Generator $ids, private Clock $clock, private TransactionManager $transactions, private TenantScopedOperationRecorder $operationRecorder) {}

    public function handle(SuspendOrganizationCommand $command): ResourceVersion
    {
        return $this->transactions->transactional(function () use ($command): ResourceVersion {
            if (! $this->policy->allows($this->tenantContext)) {
                throw ApplicationOperationFailure::forCategory(ErrorCategory::FORBIDDEN, 'The acting identity is not authorized to suspend this organization.');
            }
            $organization = $this->organizations->getById($this->tenantContext->organizationId());
            $now = $this->clock->now();
            $suspended = $organization->suspend($now, $this->tenantContext->actingIdentity()->identityId(), $this->ids->generate(), $this->tenantContext->correlationId()->value(), $this->tenantContext->causationId()?->value());
            $this->organizations->save($suspended, $command->expectedVersion());
            /** @var list<AggregateDomainEvent> $events */
            $events = $suspended->pullDomainEvents();
            $this->operationRecorder->record($events, 'organization.suspend', 'organization', $suspended->id()->value(), $now);

            return $suspended->version();
        });
    }
}
