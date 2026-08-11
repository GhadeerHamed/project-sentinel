<?php

declare(strict_types=1);

namespace Tests\Application\Event;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Event\Application\Handler\GetEventHandler;
use Sentinel\Event\Application\Policy\EventDetailVisibilityPolicy;
use Sentinel\Event\Application\Policy\EventRetrievalPolicy;
use Sentinel\Event\Application\Query\EventDetailQuery;
use Sentinel\Event\Application\ReadModel\EventDetail;
use Sentinel\Event\Domain\ValueObject\EventId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class GetEventHandlerTest extends TestCase
{
    #[Test]
    public function it_uses_a_tenant_scoped_projection_and_returns_payload_and_attributes_when_the_visibility_policy_permits_them(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f70');
        $eventId = EventId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f71');
        $events = new class implements EventDetailQuery
        {
            public ?OrganizationId $organizationId = null;

            public function get(OrganizationId $organizationId, EventId $eventId): ?EventDetail
            {
                $this->organizationId = $organizationId;

                return new EventDetail($eventId->value(), ['event_type' => 'application.error'], ['message' => 'Failure'], ['retry' => false]);
            }
        };
        $handler = new GetEventHandler($events, $this->policy(true), $this->visibility(true), $this->tenantContext($organizationId));

        $event = $handler->handle($eventId);

        self::assertSame($organizationId->value(), $events->organizationId?->value());
        self::assertSame(['message' => 'Failure'], $event->payload());
        self::assertSame(['retry' => false], $event->eventAttributes());
    }

    #[Test]
    public function it_does_not_query_events_when_retrieval_is_not_authorized(): void
    {
        $organizationId = OrganizationId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f72');
        $handler = new GetEventHandler(
            new class implements EventDetailQuery
            {
                public function get(OrganizationId $organizationId, EventId $eventId): ?EventDetail
                {
                    throw new LogicException('Unauthorized retrieval must not query events.');
                }
            },
            $this->policy(false),
            $this->visibility(true),
            $this->tenantContext($organizationId),
        );

        $this->expectException(ApplicationOperationFailure::class);
        $this->expectExceptionMessage('not authorized');
        try {
            $handler->handle(EventId::fromString('0198f3b6-48ae-7a44-9192-6f8029df5f73'));
        } catch (ApplicationOperationFailure $error) {
            self::assertSame(ErrorCategory::FORBIDDEN, $error->category());

            throw $error;
        }
    }

    private function policy(bool $allows): EventRetrievalPolicy
    {
        return new class($allows) implements EventRetrievalPolicy
        {
            public function __construct(private bool $allows) {}

            public function allows(TenantContext $tenantContext): bool
            {
                return $this->allows;
            }
        };
    }

    private function visibility(bool $allows): EventDetailVisibilityPolicy
    {
        return new class($allows) implements EventDetailVisibilityPolicy
        {
            public function __construct(private bool $allows) {}

            public function permitsPayloadAndAttributes(TenantContext $tenantContext): bool
            {
                return $this->allows;
            }
        };
    }

    private function tenantContext(OrganizationId $organizationId): TenantContext
    {
        return TenantContext::fromValues($organizationId->value(), '0198f3b6-48ae-7a44-9192-6f8029df5f74', 'user', '0198f3b6-48ae-7a44-9192-6f8029df5f75');
    }
}
