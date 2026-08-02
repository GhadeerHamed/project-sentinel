<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\Entity;

use DateTimeImmutable;
use Sentinel\IdentityAccess\Domain\Exception\InvalidIdentityAccessState;
use Sentinel\IdentityAccess\Domain\ValueObject\IdentityStatus;
use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Identity\IdentityId;

final readonly class User
{
    private function __construct(
        private IdentityId $identityId,
        private string $email,
        private string $displayName,
        private IdentityStatus $status,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ResourceVersion $version,
    ) {}

    public static function fromValues(
        string $identityId,
        string $email,
        string $displayName,
        string $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        int $version = 1,
    ): self {
        $normalizedEmail = strtolower(trim($email));
        $normalizedDisplayName = trim($displayName);

        if ($normalizedDisplayName === '') {
            throw InvalidIdentityAccessState::forField('display_name');
        }

        if (filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidIdentityAccessState::forField('email');
        }

        return new self(
            identityId: IdentityId::fromString($identityId),
            email: $normalizedEmail,
            displayName: $normalizedDisplayName,
            status: IdentityStatus::fromString($status),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            version: ResourceVersion::fromInt($version),
        );
    }

    public function identityId(): IdentityId
    {
        return $this->identityId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function status(): IdentityStatus
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

    public function canAuthenticate(): bool
    {
        return $this->status->isActive();
    }

    public function version(): ResourceVersion
    {
        return $this->version;
    }
}
