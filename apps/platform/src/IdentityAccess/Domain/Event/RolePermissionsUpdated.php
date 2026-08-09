<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Event;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Event\AggregateDomainEvent;
use Sentinel\Shared\Domain\Event\EventId;
use Sentinel\Shared\Domain\Identifier\CausationId;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class RolePermissionsUpdated implements AggregateDomainEvent
{
    /**
     * @param  list<PermissionCode>  $grantedPermissions
     * @param  list<PermissionCode>  $revokedPermissions
     */
    private function __construct(
        private EventId $eventId,
        private OrganizationId $organizationId,
        private DateTimeImmutable $occurredAt,
        private CorrelationId $correlationId,
        private ?CausationId $causationId,
        private RoleId $roleId,
        private IdentityId $actorIdentityId,
        private array $grantedPermissions,
        private array $revokedPermissions,
    ) {}

    /**
     * @param  list<PermissionCode>  $grantedPermissions
     * @param  list<PermissionCode>  $revokedPermissions
     */
    public static function fromValues(
        string $eventId,
        string $organizationId,
        DateTimeImmutable $occurredAt,
        string $correlationId,
        ?string $causationId,
        string $roleId,
        string $actorIdentityId,
        array $grantedPermissions,
        array $revokedPermissions,
    ): self {
        return new self(
            eventId: EventId::fromString($eventId),
            organizationId: OrganizationId::fromString($organizationId),
            occurredAt: $occurredAt,
            correlationId: CorrelationId::fromString($correlationId),
            causationId: $causationId === null ? null : CausationId::fromString($causationId),
            roleId: RoleId::fromString($roleId),
            actorIdentityId: IdentityId::fromString($actorIdentityId),
            grantedPermissions: $grantedPermissions,
            revokedPermissions: $revokedPermissions,
        );
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function causationId(): ?CausationId
    {
        return $this->causationId;
    }

    public function eventName(): string
    {
        return 'role.permissions_updated';
    }

    public function eventVersion(): int
    {
        return 1;
    }

    public function aggregateType(): string
    {
        return 'role';
    }

    public function aggregateId(): string
    {
        return $this->roleId->value();
    }

    public function aggregateVersion(): ResourceVersion
    {
        return ResourceVersion::fromInt(1);
    }

    /**
     * @return array{role_id: string, actor_identity_id: string, granted_permissions: list<string>, revoked_permissions: list<string>}
     */
    public function payload(): array
    {
        return [
            'role_id' => $this->roleId->value(),
            'actor_identity_id' => $this->actorIdentityId->value(),
            'granted_permissions' => array_map(static fn (PermissionCode $permission): string => $permission->value(), $this->grantedPermissions),
            'revoked_permissions' => array_map(static fn (PermissionCode $permission): string => $permission->value(), $this->revokedPermissions),
        ];
    }
}
