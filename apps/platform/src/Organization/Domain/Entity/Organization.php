<?php

declare(strict_types=1);

namespace Sentinel\Organization\Domain\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Sentinel\Organization\Domain\Event\OrganizationArchived;
use Sentinel\Organization\Domain\Event\OrganizationCreated;
use Sentinel\Organization\Domain\Event\OrganizationReactivated;
use Sentinel\Organization\Domain\Event\OrganizationSuspended;
use Sentinel\Organization\Domain\Event\OrganizationUpdated;
use Sentinel\Organization\Domain\Exception\InvalidOrganization;
use Sentinel\Organization\Domain\Exception\InvalidOrganizationTransition;
use Sentinel\Organization\Domain\ValueObject\OrganizationStatus;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\DomainEvent;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class Organization
{
    private function __construct(
        private OrganizationId $id,
        private string $name,
        private string $slug,
        private string $timezone,
        private OrganizationStatus $status,
        private IdentityId $createdByIdentityId,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ResourceVersion $version,
        /** @var list<DomainEvent> */
        private array $recordedEvents = [],
    ) {}

    public static function create(
        string $id,
        string $name,
        string $slug,
        string $createdByIdentityId,
        DateTimeImmutable $at,
        string $eventId,
        string $correlationId,
        ?string $causationId = null,
        string $timezone = 'UTC',
    ): self {
        $organization = self::fromValues(
            id: $id,
            name: $name,
            slug: $slug,
            timezone: $timezone,
            status: OrganizationStatus::ACTIVE->value,
            createdByIdentityId: $createdByIdentityId,
            createdAt: $at,
            updatedAt: $at,
            version: 1,
        );

        return $organization->withEvent(OrganizationCreated::fromValues(
            eventId: $eventId,
            organizationId: $id,
            occurredAt: $at,
            correlationId: $correlationId,
            causationId: $causationId,
            actorIdentityId: $createdByIdentityId,
            organizationName: $organization->name,
            organizationSlug: $organization->slug,
            organizationStatus: $organization->status,
            aggregateVersion: $organization->version->value(),
        ));
    }

    public static function fromValues(
        string $id,
        string $name,
        string $slug,
        string $status,
        string $createdByIdentityId,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version,
        string $timezone = 'UTC',
    ): self {
        $normalizedName = trim($name);
        $normalizedSlug = strtolower(trim($slug));
        $normalizedTimezone = trim($timezone);

        if ($normalizedName === '') {
            throw InvalidOrganization::forField('name');
        }

        if ($normalizedSlug === '') {
            throw InvalidOrganization::forField('slug');
        }

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $normalizedSlug) !== 1) {
            throw InvalidOrganization::forField('slug');
        }

        try {
            new DateTimeZone($normalizedTimezone);
        } catch (\Exception) {
            throw InvalidOrganization::forField('timezone');
        }

        return new self(
            id: OrganizationId::fromString($id),
            name: $normalizedName,
            slug: $normalizedSlug,
            timezone: $normalizedTimezone,
            status: OrganizationStatus::fromString($status),
            createdByIdentityId: IdentityId::fromString($createdByIdentityId),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            version: ResourceVersion::fromInt($version),
        );
    }

    public function id(): OrganizationId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function status(): OrganizationStatus
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

    public function version(): ResourceVersion
    {
        return $this->version;
    }

    /**
     * @return list<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function suspend(
        DateTimeImmutable $at,
        string $actorIdentityId,
        string $eventId,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        if ($this->status !== OrganizationStatus::ACTIVE) {
            throw InvalidOrganizationTransition::fromTo($this->status, OrganizationStatus::SUSPENDED);
        }

        $organization = $this->withStatus(OrganizationStatus::SUSPENDED, $at);

        return $organization->withEvent(OrganizationSuspended::fromValues(
            eventId: $eventId,
            organizationId: $this->id->value(),
            occurredAt: $at,
            correlationId: $correlationId,
            causationId: $causationId,
            actorIdentityId: $actorIdentityId,
            organizationStatus: OrganizationStatus::SUSPENDED,
            aggregateVersion: $organization->version->value(),
        ));
    }

    public function reactivate(
        DateTimeImmutable $at,
        string $actorIdentityId,
        string $eventId,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        if ($this->status !== OrganizationStatus::SUSPENDED) {
            throw InvalidOrganizationTransition::fromTo($this->status, OrganizationStatus::ACTIVE);
        }

        $organization = $this->withStatus(OrganizationStatus::ACTIVE, $at);

        return $organization->withEvent(OrganizationReactivated::fromValues(
            eventId: $eventId,
            organizationId: $this->id->value(),
            occurredAt: $at,
            correlationId: $correlationId,
            causationId: $causationId,
            actorIdentityId: $actorIdentityId,
            organizationStatus: OrganizationStatus::ACTIVE,
            aggregateVersion: $organization->version->value(),
        ));
    }

    public function archive(
        DateTimeImmutable $at,
        string $actorIdentityId,
        string $eventId,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        if (
            $this->status !== OrganizationStatus::ACTIVE
            && $this->status !== OrganizationStatus::SUSPENDED
        ) {
            throw InvalidOrganizationTransition::fromTo($this->status, OrganizationStatus::ARCHIVED);
        }

        $organization = $this->withStatus(OrganizationStatus::ARCHIVED, $at);

        return $organization->withEvent(OrganizationArchived::fromValues(
            eventId: $eventId,
            organizationId: $this->id->value(),
            occurredAt: $at,
            correlationId: $correlationId,
            causationId: $causationId,
            actorIdentityId: $actorIdentityId,
            organizationStatus: OrganizationStatus::ARCHIVED,
            aggregateVersion: $organization->version->value(),
        ));
    }

    public function updateProfile(
        string $name,
        string $timezone,
        DateTimeImmutable $at,
        string $actorIdentityId,
        string $eventId,
        string $correlationId,
        ?string $causationId = null,
    ): self {
        $updated = self::fromValues(
            id: $this->id->value(),
            name: $name,
            slug: $this->slug,
            status: $this->status->value,
            createdByIdentityId: $this->createdByIdentityId->value(),
            createdAt: $this->createdAt,
            updatedAt: $at,
            version: $this->version->next()->value(),
            timezone: $timezone,
        );

        return $updated->withEvent(OrganizationUpdated::fromValues(
            eventId: $eventId,
            organizationId: $this->id->value(),
            occurredAt: $at,
            correlationId: $correlationId,
            causationId: $causationId,
            actorIdentityId: $actorIdentityId,
            organizationName: $updated->name,
            organizationTimezone: $updated->timezone,
            aggregateVersion: $updated->version->value(),
        ));
    }

    private function withStatus(OrganizationStatus $status, DateTimeImmutable $updatedAt): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            timezone: $this->timezone,
            status: $status,
            createdByIdentityId: $this->createdByIdentityId,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            version: $this->version->next(),
            recordedEvents: $this->recordedEvents,
        );
    }

    private function withEvent(DomainEvent $event): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            timezone: $this->timezone,
            status: $this->status,
            createdByIdentityId: $this->createdByIdentityId,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            version: $this->version,
            recordedEvents: [...$this->recordedEvents, $event],
        );
    }
}
