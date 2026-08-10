<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Entity;

use DateTimeImmutable;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Event\Domain\Exception\InvalidEventSource;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Event\Domain\ValueObject\EventSourceStatus;
use Sentinel\Event\Domain\ValueObject\EventSourceType;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class EventSource
{
    private function __construct(
        private EventSourceId $id,
        private OrganizationId $organizationId,
        private ProjectId $projectId,
        private ApplicationId $applicationId,
        private EnvironmentId $environmentId,
        private EventSourceType $sourceType,
        private string $name,
        private EventSourceStatus $status,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ResourceVersion $version,
    ) {}

    public static function create(
        string $id,
        string $organizationId,
        string $projectId,
        string $applicationId,
        string $environmentId,
        string $name,
        DateTimeImmutable $at,
    ): self {
        return self::fromValues(
            id: $id,
            organizationId: $organizationId,
            projectId: $projectId,
            applicationId: $applicationId,
            environmentId: $environmentId,
            sourceType: EventSourceType::API_CLIENT->value,
            name: $name,
            status: EventSourceStatus::ACTIVE->value,
            createdAt: $at,
            updatedAt: $at,
            version: 1,
        );
    }

    public static function fromValues(
        string $id,
        string $organizationId,
        string $projectId,
        string $applicationId,
        string $environmentId,
        string $sourceType,
        string $name,
        string $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
    ): self {
        $normalizedName = trim($name);

        if ($normalizedName === '' || mb_strlen($normalizedName) > 255) {
            throw InvalidEventSource::forField('name');
        }

        return new self(
            id: EventSourceId::fromString($id),
            organizationId: OrganizationId::fromString($organizationId),
            projectId: ProjectId::fromString($projectId),
            applicationId: ApplicationId::fromString($applicationId),
            environmentId: EnvironmentId::fromString($environmentId),
            sourceType: EventSourceType::fromString($sourceType),
            name: $normalizedName,
            status: EventSourceStatus::fromString($status),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            version: ResourceVersion::fromInt($version),
        );
    }

    public function id(): EventSourceId
    {
        return $this->id;
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

    public function sourceType(): EventSourceType
    {
        return $this->sourceType;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): EventSourceStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function version(): ResourceVersion
    {
        return $this->version;
    }

    public function isActive(): bool
    {
        return $this->status === EventSourceStatus::ACTIVE;
    }
}
