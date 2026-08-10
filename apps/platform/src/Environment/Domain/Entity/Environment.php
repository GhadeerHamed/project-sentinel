<?php

declare(strict_types=1);

namespace Sentinel\Environment\Domain\Entity;

use DateTimeImmutable;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\Event\EnvironmentCreated;
use Sentinel\Environment\Domain\Exception\InvalidEnvironment;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentStatus;
use Sentinel\Environment\Domain\ValueObject\EnvironmentType;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\DomainEvent;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class Environment
{
    private function __construct(
        private EnvironmentId $id,
        private OrganizationId $organizationId,
        private ApplicationId $applicationId,
        private string $name,
        private string $slug,
        private EnvironmentType $environmentType,
        private EnvironmentStatus $status,
        private IdentityId $createdByIdentityId,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $archivedAt,
        private ResourceVersion $version,
        /** @var list<DomainEvent> */
        private array $recordedEvents = [],
    ) {}

    public static function create(
        string $id,
        string $organizationId,
        string $applicationId,
        string $name,
        string $slug,
        string $environmentType,
        string $createdByIdentityId,
        DateTimeImmutable $at,
        string $eventId,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        $environment = self::fromValues(
            id: $id,
            organizationId: $organizationId,
            applicationId: $applicationId,
            name: $name,
            slug: $slug,
            environmentType: $environmentType,
            status: EnvironmentStatus::ACTIVE->value,
            createdByIdentityId: $createdByIdentityId,
            createdAt: $at,
            updatedAt: $at,
            archivedAt: null,
            version: 1,
        );

        return $environment->withEvent(EnvironmentCreated::fromValues(
            eventId: $eventId,
            organizationId: $organizationId,
            applicationId: $applicationId,
            environmentId: $id,
            occurredAt: $at,
            correlationId: $correlationId,
            causationId: $causationId,
            actorIdentityId: $createdByIdentityId,
            environmentName: $environment->name,
            environmentSlug: $environment->slug,
            environmentType: $environment->environmentType,
            environmentStatus: $environment->status,
            aggregateVersion: $environment->version->value(),
        ));
    }

    public static function fromValues(
        string $id,
        string $organizationId,
        string $applicationId,
        string $name,
        string $slug,
        string $environmentType,
        string $status,
        string $createdByIdentityId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $archivedAt,
        int $version,
    ): self {
        $normalizedName = trim($name);
        $normalizedSlug = strtolower(trim($slug));

        if ($normalizedName === '' || mb_strlen($normalizedName) > 255) {
            throw InvalidEnvironment::forField('name');
        }

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $normalizedSlug) !== 1 || mb_strlen($normalizedSlug) > 255) {
            throw InvalidEnvironment::forField('slug');
        }

        return new self(
            id: EnvironmentId::fromString($id),
            organizationId: OrganizationId::fromString($organizationId),
            applicationId: ApplicationId::fromString($applicationId),
            name: $normalizedName,
            slug: $normalizedSlug,
            environmentType: EnvironmentType::fromString($environmentType),
            status: EnvironmentStatus::fromString($status),
            createdByIdentityId: IdentityId::fromString($createdByIdentityId),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            archivedAt: $archivedAt,
            version: ResourceVersion::fromInt($version),
        );
    }

    public function id(): EnvironmentId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function applicationId(): ApplicationId
    {
        return $this->applicationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function environmentType(): EnvironmentType
    {
        return $this->environmentType;
    }

    public function status(): EnvironmentStatus
    {
        return $this->status;
    }

    public function createdByIdentityId(): IdentityId
    {
        return $this->createdByIdentityId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function archivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function version(): ResourceVersion
    {
        return $this->version;
    }

    /** @return list<DomainEvent> */
    public function pullDomainEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function withEvent(DomainEvent $event): self
    {
        $copy = clone $this;
        $copy->recordedEvents[] = $event;

        return $copy;
    }
}
