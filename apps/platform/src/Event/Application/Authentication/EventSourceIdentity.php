<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Authentication;

use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Event\Domain\Entity\EventSource;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class EventSourceIdentity
{
    public const REQUEST_ATTRIBUTE = 'event_source_identity';

    private function __construct(
        private EventSourceId $eventSourceId,
        private OrganizationId $organizationId,
        private ProjectId $projectId,
        private ApplicationId $applicationId,
        private EnvironmentId $environmentId,
    ) {}

    public static function fromEventSource(EventSource $eventSource): self
    {
        return new self(
            eventSourceId: $eventSource->id(),
            organizationId: $eventSource->organizationId(),
            projectId: $eventSource->projectId(),
            applicationId: $eventSource->applicationId(),
            environmentId: $eventSource->environmentId(),
        );
    }

    public function eventSourceId(): EventSourceId
    {
        return $this->eventSourceId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function applicationId(): ApplicationId
    {
        return $this->applicationId;
    }

    public function environmentId(): EnvironmentId
    {
        return $this->environmentId;
    }
}
