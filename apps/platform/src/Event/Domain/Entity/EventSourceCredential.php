<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Entity;

use DateTimeImmutable;
use Sentinel\Event\Domain\Exception\InvalidEventSourceCredential;
use Sentinel\Event\Domain\ValueObject\EventSourceCredentialId;
use Sentinel\Event\Domain\ValueObject\EventSourceCredentialStatus;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class EventSourceCredential
{
    private function __construct(
        private EventSourceCredentialId $id,
        private OrganizationId $organizationId,
        private EventSourceId $eventSourceId,
        private string $secretHash,
        private EventSourceCredentialStatus $status,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $revokedAt,
        private ?DateTimeImmutable $expiresAt,
    ) {}

    public static function issue(
        string $id,
        string $organizationId,
        string $eventSourceId,
        string $secretHash,
        DateTimeImmutable $at,
    ): self {
        return self::fromValues(
            id: $id,
            organizationId: $organizationId,
            eventSourceId: $eventSourceId,
            secretHash: $secretHash,
            status: EventSourceCredentialStatus::ACTIVE->value,
            createdAt: $at,
            revokedAt: null,
            expiresAt: null,
        );
    }

    public static function fromValues(
        string $id,
        string $organizationId,
        string $eventSourceId,
        string $secretHash,
        string $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $revokedAt,
        ?DateTimeImmutable $expiresAt,
    ): self {
        $normalizedSecretHash = trim($secretHash);

        if ($normalizedSecretHash === '') {
            throw InvalidEventSourceCredential::forField('secret_hash');
        }

        return new self(
            id: EventSourceCredentialId::fromString($id),
            organizationId: OrganizationId::fromString($organizationId),
            eventSourceId: EventSourceId::fromString($eventSourceId),
            secretHash: $normalizedSecretHash,
            status: EventSourceCredentialStatus::fromString($status),
            createdAt: $createdAt,
            revokedAt: $revokedAt,
            expiresAt: $expiresAt,
        );
    }

    public function id(): EventSourceCredentialId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function eventSourceId(): EventSourceId
    {
        return $this->eventSourceId;
    }

    public function secretHash(): string
    {
        return $this->secretHash;
    }

    public function status(): EventSourceCredentialStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isActiveAt(DateTimeImmutable $at): bool
    {
        return $this->status === EventSourceCredentialStatus::ACTIVE
            && ($this->expiresAt === null || $this->expiresAt > $at);
    }

    public function matchesSecret(string $secret): bool
    {
        return password_verify($secret, $this->secretHash);
    }
}
