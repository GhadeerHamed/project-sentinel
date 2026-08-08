<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Entity;

use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleKey;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class OrganizationRole
{
    private function __construct(
        private RoleId $id,
        private OrganizationId $organizationId,
        private RoleKey $key,
    ) {}

    public static function fromValues(string $id, string $organizationId, string $key): self
    {
        return new self(
            RoleId::fromString($id),
            OrganizationId::fromString($organizationId),
            RoleKey::fromString($key),
        );
    }

    public function id(): RoleId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function key(): RoleKey
    {
        return $this->key;
    }
}
